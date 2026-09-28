<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class ContentService
{
    public static function validate(array $data): Validator
    {
        return (new Validator($data))
            ->required('title', 'Title')->max('title', 160, 'Title')
            ->max('caption', 2200, 'Caption')
            ->in('status', array_keys(Catalog::CONTENT_STATUSES), 'status')
            ->in('format', Catalog::FORMATS, 'format')
            ->date('publish_at');
    }

    /** Create or update a content item. Returns its id. */
    public static function save(array $data, ?int $id, int $userId): int
    {
        $validator = self::validate($data);
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->firstError());
        }
        $platforms = array_values(array_intersect((array) ($data['platforms'] ?? []), array_keys(Catalog::PLATFORMS)));
        if ($platforms === []) {
            throw new InvalidArgumentException('Choose at least one platform.');
        }

        $publishAt = trim((string) ($data['publish_at'] ?? ''));
        $fields = [
            trim((string) $data['title']),
            ($data['project_id'] ?? '') !== '' ? (int) $data['project_id'] : null,
            $data['format'] ?? 'Photo',
            trim((string) ($data['caption'] ?? '')),
            $publishAt !== '' ? date('Y-m-d H:i', strtotime($publishAt)) : null,
            ($data['assigned_to'] ?? '') !== '' ? (int) $data['assigned_to'] : null,
            trim((string) ($data['internal_notes'] ?? '')),
        ];
        $status = $data['status'] ?? 'draft';
        if ($status === 'awaiting_approval') {
            $status = 'draft'; // entering approval always goes through submit() so it is versioned and logged
        }

        if ($id === null) {
            q('INSERT INTO content_items (title, project_id, format, caption, publish_at, assigned_to, internal_notes, status, created_by)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [...$fields, $status, $userId]);
            $id = (int) db()->lastInsertId();
            log_activity('content.created', "Content #{$id} created");
        } else {
            q('UPDATE content_items SET title = ?, project_id = ?, format = ?, caption = ?, publish_at = ?, assigned_to = ?,
               internal_notes = ?, status = ?, updated_at = now_local() WHERE id = ?', [...$fields, $status, $id]);
        }

        q('DELETE FROM content_platforms WHERE content_id = ?', [$id]);
        foreach ($platforms as $platform) {
            q('INSERT INTO content_platforms (content_id, platform) VALUES (?, ?)', [$id, $platform]);
        }
        foreach ((array) ($data['asset_ids'] ?? []) as $assetId) {
            self::attachAsset($id, (int) $assetId);
        }
        return $id;
    }

    public static function attachAsset(int $contentId, int $assetId): void
    {
        if ($assetId > 0 && row('SELECT id FROM assets WHERE id = ?', [$assetId])) {
            q('INSERT INTO content_assets (content_id, asset_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [$contentId, $assetId]);
        }
    }

    /** Send an item for approval: bumps the version (after the first round) and snapshots it. */
    public static function submit(int $id, int $userId): void
    {
        $item = row('SELECT * FROM content_items WHERE id = ?', [$id]);
        if ($item === null) {
            throw new InvalidArgumentException('Content not found.');
        }
        $hasRound = (int) scalar("SELECT COUNT(*) FROM content_approvals WHERE content_id = ? AND action = 'submitted'", [$id]) > 0;
        $version = $hasRound ? (int) $item['version'] + 1 : (int) $item['version'];
        $snapshot = json_encode([
            'title' => $item['title'],
            'caption' => $item['caption'],
            'assets' => array_column(rows('SELECT a.title FROM assets a JOIN content_assets ca ON ca.asset_id = a.id WHERE ca.content_id = ?', [$id]), 'title'),
        ]);
        q("UPDATE content_items SET status = 'awaiting_approval', version = ?, updated_at = now_local() WHERE id = ?", [$version, $id]);
        q("INSERT INTO content_approvals (content_id, user_id, action, version, snapshot) VALUES (?, ?, 'submitted', ?, ?)", [$id, $userId, $version, $snapshot]);
        log_activity('content.submitted', "Content #{$id} sent for approval (v{$version})");
    }

    /** Approve or request changes on an item that is awaiting approval. */
    public static function review(int $id, string $decision, int $userId, string $note = ''): void
    {
        $item = row('SELECT * FROM content_items WHERE id = ?', [$id]);
        if ($item === null) {
            throw new InvalidArgumentException('Content not found.');
        }
        if ($item['status'] !== 'awaiting_approval') {
            throw new InvalidArgumentException('Only items awaiting approval can be reviewed.');
        }
        if (!in_array($decision, ['approved', 'changes_requested'], true)) {
            throw new InvalidArgumentException('Unknown decision.');
        }
        if ($decision === 'changes_requested' && trim($note) === '') {
            throw new InvalidArgumentException('Tell the team what needs to change.');
        }
        $newStatus = $decision === 'approved' ? 'approved' : 'in_production';
        q('UPDATE content_items SET status = ?, updated_at = now_local() WHERE id = ?', [$newStatus, $id]);
        q('INSERT INTO content_approvals (content_id, user_id, action, version, note) VALUES (?, ?, ?, ?, ?)', [$id, $userId, $decision, $item['version'], trim($note) ?: null]);
        if (trim($note) !== '') {
            self::comment($id, $userId, trim($note));
        }
        log_activity('content.' . $decision, "Content #{$id} {$decision}");
    }

    public static function comment(int $id, int $userId, string $body): void
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 2000) {
            throw new InvalidArgumentException('Comments must be between 1 and 2000 characters.');
        }
        $version = (int) scalar('SELECT version FROM content_items WHERE id = ?', [$id]);
        q('INSERT INTO content_comments (content_id, user_id, version, body) VALUES (?, ?, ?, ?)', [$id, $userId, $version, $body]);
    }
}

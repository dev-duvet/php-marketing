<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\Catalog;
use App\Services\ContentService;
use App\Services\Upload;
use InvalidArgumentException;
use RuntimeException;

final class ContentController extends Controller
{
    /** Items for a Mon–Sun week, keyed [platform][Y-m-d] => list of items. */
    public static function weekGrid(string $weekStart): array
    {
        [$scope, $params] = Auth::clientScope();
        $end = date('Y-m-d', strtotime($weekStart . ' +7 days'));
        $items = rows(
            "SELECT c.id, c.title, c.format, c.status, c.publish_at, cp.platform
             FROM content_items c JOIN content_platforms cp ON cp.content_id = c.id
             LEFT JOIN projects p ON p.id = c.project_id
             WHERE c.publish_at >= ? AND c.publish_at < ?{$scope} ORDER BY c.publish_at",
            [$weekStart, $end, ...$params]
        );
        $grid = [];
        foreach ($items as $item) {
            $grid[$item['platform']][substr($item['publish_at'], 0, 10)][] = $item;
        }
        return $grid;
    }

    public function calendar(): void
    {
        $week = input('week');
        $base = preg_match('/^\d{4}-\d{2}-\d{2}$/', $week) && strtotime($week) ? strtotime($week) : time();
        $weekStart = date('Y-m-d', strtotime('monday this week', $base));
        [$scope, $params] = Auth::clientScope();
        $status = input('status');
        $filter = isset(Catalog::CONTENT_STATUSES[$status]) ? ' AND c.status = ?' : '';

        $this->admin('content/calendar', [
            'title' => 'Content calendar',
            'weekStart' => $weekStart,
            'grid' => self::weekGrid($weekStart),
            'status' => $status,
            'items' => rows(
                "SELECT c.*, p.name AS project_name, u.name AS assignee,
                        (SELECT " . group_concat('platform') . " FROM content_platforms WHERE content_id = c.id) AS platforms
                 FROM content_items c LEFT JOIN projects p ON p.id = c.project_id LEFT JOIN users u ON u.id = c.assigned_to
                 WHERE 1=1{$scope}{$filter} ORDER BY c.publish_at IS NULL, c.publish_at",
                $filter ? [...$params, $status] : $params
            ),
        ]);
    }

    private function formData(?array $item = null): array
    {
        $id = $item['id'] ?? 0;
        return [
            'title' => $item ? 'Edit content' : 'Content editor',
            'item' => $item,
            'platforms' => $item ? array_column(rows('SELECT platform FROM content_platforms WHERE content_id = ?', [$id]), 'platform') : ['instagram'],
            'attached' => rows('SELECT a.* FROM assets a JOIN content_assets ca ON ca.asset_id = a.id WHERE ca.content_id = ?', [$id]),
            'projects' => rows('SELECT p.id, p.name, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id ORDER BY p.name'),
            'team' => rows("SELECT id, name FROM users WHERE role != 'client' AND is_active = 1 ORDER BY name"),
            'library' => rows('SELECT id, title, path, type FROM assets ORDER BY created_at DESC LIMIT 40'),
        ];
    }

    public function create(): void
    {
        $this->admin('content/form', $this->formData());
    }

    public function edit(string $id): void
    {
        $item = row('SELECT * FROM content_items WHERE id = ?', [(int) $id]);
        if (!$item) {
            $this->notFound();
        }
        $this->admin('content/form', $this->formData($item));
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(string $id): void
    {
        if (!row('SELECT id FROM content_items WHERE id = ?', [(int) $id])) {
            $this->notFound();
        }
        $this->save((int) $id);
    }

    private function save(?int $id): void
    {
        $back = $id ? "/admin/content/{$id}/edit" : '/admin/content/new';
        $userId = (int) Auth::id();
        try {
            $data = $_POST;
            // Uploaded files become library assets and are attached to this item.
            foreach (Upload::files('uploads') as $file) {
                $stored = Upload::store($file);
                q('INSERT INTO assets (title, path, mime, type, size, project_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [
                    pathinfo($file['name'], PATHINFO_FILENAME), $stored['path'], $stored['mime'], $stored['type'], $stored['size'],
                    ($data['project_id'] ?? '') !== '' ? (int) $data['project_id'] : null, $userId,
                ]);
                $data['asset_ids'][] = (int) db()->lastInsertId();
            }
            $id = ContentService::save($data, $id, $userId);
            if (input('action') === 'submit') {
                ContentService::submit($id, $userId);
                flash('success', 'Sent for approval.');
                redirect('/admin/approvals/' . $id);
            }
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->failed($e->getMessage(), $back);
        }
        flash('success', 'Content saved.');
        redirect("/admin/content/{$id}/edit");
    }

    public function destroy(string $id): void
    {
        if (input('confirm') !== 'yes') {
            $this->failed('Please confirm the deletion.', "/admin/content/{$id}/edit");
        }
        q('DELETE FROM content_items WHERE id = ?', [(int) $id]);
        log_activity('content.deleted', "Content #{$id} deleted");
        flash('success', 'Content deleted. Attached assets remain in the library.');
        redirect('/admin/content');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\Catalog;
use App\Services\ContentService;
use App\Services\Upload;
use RuntimeException;

final class AssetController extends Controller
{
    private function find(int $id): array
    {
        [$scope, $params] = Auth::clientScope();
        $asset = row("SELECT a.*, p.name AS project_name FROM assets a LEFT JOIN projects p ON p.id = a.project_id WHERE a.id = ?{$scope}", [$id, ...$params]);
        if (!$asset) {
            $this->notFound();
        }
        return $asset;
    }

    public function index(): void
    {
        [$scope, $params] = Auth::clientScope();
        $where = "1=1{$scope}";
        $filters = ['q' => input('q'), 'type' => input('type'), 'project' => input('project'), 'tag' => input('tag')];
        if ($filters['q'] !== '') {
            $where .= ' AND LOWER(a.title) LIKE ?';
            $params[] = '%' . mb_strtolower($filters['q']) . '%';
        }
        if (in_array($filters['type'], ['image', 'video', 'design', 'document'], true)) {
            $where .= ' AND a.type = ?';
            $params[] = $filters['type'];
        }
        if ($filters['project'] !== '') {
            $where .= ' AND a.project_id = ?';
            $params[] = (int) $filters['project'];
        }
        if ($filters['tag'] !== '') {
            $where .= ' AND a.id IN (SELECT m.asset_id FROM asset_tag_map m JOIN asset_tags t ON t.id = m.tag_id WHERE t.name = ?)';
            $params[] = $filters['tag'];
        }
        $this->admin('assets/index', [
            'title' => 'Asset library',
            'filters' => $filters,
            'assets' => rows(
                "SELECT a.*, p.name AS project_name,
                        (SELECT " . group_concat('t.name', ', ') . " FROM asset_tag_map m JOIN asset_tags t ON t.id = m.tag_id WHERE m.asset_id = a.id) AS tags
                 FROM assets a LEFT JOIN projects p ON p.id = a.project_id WHERE {$where} ORDER BY a.created_at DESC",
                $params
            ),
            'projects' => rows('SELECT id, name FROM projects ORDER BY name'),
            'tags' => array_column(rows('SELECT name FROM asset_tags ORDER BY name'), 'name'),
        ]);
    }

    public function upload(): void
    {
        $files = Upload::files('files');
        if ($files === []) {
            $this->failed('Choose at least one file to upload.', '/admin/assets');
        }
        $projectId = input('project_id') !== '' ? (int) input('project_id') : null;
        $saved = 0;
        $errors = [];
        foreach ($files as $file) {
            try {
                $stored = Upload::store($file);
                q('INSERT INTO assets (title, path, mime, type, size, project_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [
                    mb_substr(pathinfo($file['name'], PATHINFO_FILENAME), 0, 120) ?: 'Untitled', $stored['path'], $stored['mime'],
                    $stored['type'], $stored['size'], $projectId, Auth::id(),
                ]);
                $this->syncTags((int) db()->lastInsertId(), input('tags'));
                $saved++;
            } catch (RuntimeException $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }
        if ($saved) {
            log_activity('asset.uploaded', "{$saved} asset(s) uploaded");
            flash('success', "{$saved} file(s) uploaded.");
        }
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('/admin/assets');
    }

    public function show(string $id): void
    {
        $asset = $this->find((int) $id);
        $this->admin('assets/show', [
            'title' => $asset['title'],
            'asset' => $asset,
            'tags' => array_column(rows('SELECT t.name FROM asset_tag_map m JOIN asset_tags t ON t.id = m.tag_id WHERE m.asset_id = ?', [$asset['id']]), 'name'),
            'usedIn' => rows('SELECT c.id, c.title, c.status FROM content_items c JOIN content_assets ca ON ca.content_id = c.id WHERE ca.asset_id = ?', [$asset['id']]),
            'projects' => rows('SELECT id, name FROM projects ORDER BY name'),
            'contentItems' => rows("SELECT id, title FROM content_items WHERE status != 'published' ORDER BY publish_at"),
        ]);
    }

    public function update(string $id): void
    {
        $asset = $this->find((int) $id);
        $title = input('title');
        if ($title === '' || mb_strlen($title) > 120) {
            $this->failed('Title must be between 1 and 120 characters.', "/admin/assets/{$id}");
        }
        $category = input('public_category');
        q('UPDATE assets SET title = ?, project_id = ?, public_category = ? WHERE id = ?', [
            $title,
            input('project_id') !== '' ? (int) input('project_id') : null,
            isset(Catalog::GALLERY_CATEGORIES[$category]) ? $category : null,
            $asset['id'],
        ]);
        $this->syncTags((int) $asset['id'], input('tags'), true);
        flash('success', 'Asset updated.');
        redirect("/admin/assets/{$id}");
    }

    public function attach(string $id): void
    {
        $asset = $this->find((int) $id);
        $contentId = (int) input('content_id');
        if (!row('SELECT id FROM content_items WHERE id = ?', [$contentId])) {
            $this->failed('Choose a content item.', "/admin/assets/{$id}");
        }
        ContentService::attachAsset($contentId, (int) $asset['id']);
        flash('success', 'Asset attached to content.');
        redirect("/admin/assets/{$id}");
    }

    public function destroy(string $id): void
    {
        $asset = $this->find((int) $id);
        if (input('confirm') !== 'yes') {
            $this->failed('Tick the confirmation box to delete this asset.', "/admin/assets/{$id}");
        }
        q('DELETE FROM assets WHERE id = ?', [$asset['id']]);
        Upload::delete($asset['path']);
        log_activity('asset.deleted', "Asset #{$asset['id']} deleted ({$asset['title']})");
        flash('success', 'Asset deleted.');
        redirect('/admin/assets');
    }

    private function syncTags(int $assetId, string $tags, bool $replace = false): void
    {
        if ($replace) {
            q('DELETE FROM asset_tag_map WHERE asset_id = ?', [$assetId]);
        }
        foreach (array_filter(array_map('trim', explode(',', $tags))) as $tag) {
            $tag = mb_substr(strtolower($tag), 0, 40);
            q('INSERT INTO asset_tags (name) VALUES (?) ON CONFLICT DO NOTHING', [$tag]);
            $tagId = (int) scalar('SELECT id FROM asset_tags WHERE name = ?', [$tag]);
            q('INSERT INTO asset_tag_map (asset_id, tag_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [$assetId, $tagId]);
        }
    }
}

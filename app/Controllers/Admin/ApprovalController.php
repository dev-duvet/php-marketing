<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\ContentService;
use InvalidArgumentException;

final class ApprovalController extends Controller
{
    /** Load a content item, enforcing client scoping. */
    private function item(int $id): array
    {
        [$scope, $params] = Auth::clientScope();
        $item = row(
            "SELECT c.*, p.name AS project_name, cl.name AS client_name FROM content_items c
             LEFT JOIN projects p ON p.id = c.project_id LEFT JOIN clients cl ON cl.id = p.client_id
             WHERE c.id = ?{$scope}",
            [$id, ...$params]
        );
        if (!$item) {
            $this->notFound();
        }
        return $item;
    }

    public function index(): void
    {
        [$scope, $params] = Auth::clientScope();
        $first = row(
            "SELECT c.id FROM content_items c LEFT JOIN projects p ON p.id = c.project_id
             WHERE c.status = 'awaiting_approval'{$scope} ORDER BY c.publish_at LIMIT 1",
            $params
        );
        if ($first) {
            redirect('/admin/approvals/' . $first['id']);
        }
        $this->admin('approvals/show', ['title' => 'Approvals', 'item' => null, 'queue' => []]);
    }

    public function show(string $id): void
    {
        $item = $this->item((int) $id);
        [$scope, $params] = Auth::clientScope();
        $this->admin('approvals/show', [
            'title' => 'Approvals',
            'item' => $item,
            'queue' => rows(
                "SELECT c.id, c.title, c.format, c.publish_at FROM content_items c LEFT JOIN projects p ON p.id = c.project_id
                 WHERE c.status = 'awaiting_approval'{$scope} ORDER BY c.publish_at",
                $params
            ),
            'assets' => rows('SELECT a.* FROM assets a JOIN content_assets ca ON ca.asset_id = a.id WHERE ca.content_id = ?', [$item['id']]),
            'platforms' => array_column(rows('SELECT platform FROM content_platforms WHERE content_id = ?', [$item['id']]), 'platform'),
            'comments' => rows('SELECT cc.*, u.name, u.role FROM content_comments cc LEFT JOIN users u ON u.id = cc.user_id WHERE cc.content_id = ? ORDER BY cc.created_at', [$item['id']]),
            'history' => rows('SELECT ca.*, u.name FROM content_approvals ca LEFT JOIN users u ON u.id = ca.user_id WHERE ca.content_id = ? ORDER BY ca.created_at DESC, ca.id DESC', [$item['id']]),
        ]);
    }

    public function comment(string $id): void
    {
        $item = $this->item((int) $id);
        try {
            ContentService::comment((int) $item['id'], (int) Auth::id(), input('body'));
        } catch (InvalidArgumentException $e) {
            $this->failed($e->getMessage(), "/admin/approvals/{$id}");
        }
        redirect("/admin/approvals/{$id}#comments");
    }

    public function decide(string $id): void
    {
        $item = $this->item((int) $id);
        try {
            ContentService::review((int) $item['id'], input('decision'), (int) Auth::id(), input('note'));
        } catch (InvalidArgumentException $e) {
            $this->failed($e->getMessage(), "/admin/approvals/{$id}");
        }
        flash('success', input('decision') === 'approved' ? 'Approved — the team can now schedule it.' : 'Changes requested. The team has been notified in the thread.');
        redirect("/admin/approvals/{$id}");
    }
}

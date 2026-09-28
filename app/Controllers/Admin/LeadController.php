<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\Catalog;
use App\Services\LeadService;
use App\Services\Validator;
use InvalidArgumentException;

final class LeadController extends Controller
{
    public function index(): void
    {
        $search = input('q');
        $assigned = input('assigned');
        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= ' AND (LOWER(l.name) LIKE ? OR LOWER(l.email) LIKE ? OR LOWER(l.organisation) LIKE ?)';
            $like = '%' . mb_strtolower($search) . '%';
            array_push($params, $like, $like, $like);
        }
        if ($assigned !== '') {
            $where .= ' AND l.assigned_to = ?';
            $params[] = (int) $assigned;
        }
        $leads = rows(
            "SELECT l.*, u.name AS assignee, (SELECT COUNT(*) FROM lead_activities WHERE lead_id = l.id) AS activity_count
             FROM leads l LEFT JOIN users u ON u.id = l.assigned_to WHERE {$where} ORDER BY l.updated_at DESC",
            $params
        );
        $columns = array_fill_keys(array_keys(Catalog::LEAD_STATUSES), []);
        foreach ($leads as $lead) {
            $columns[$lead['status']][] = $lead;
        }
        $this->admin('leads/index', [
            'title' => 'Leads',
            'columns' => $columns,
            'search' => $search,
            'assigned' => $assigned,
            'team' => rows("SELECT id, name FROM users WHERE role != 'client' ORDER BY name"),
        ]);
    }

    public function show(string $id): void
    {
        $lead = row('SELECT l.*, u.name AS assignee FROM leads l LEFT JOIN users u ON u.id = l.assigned_to WHERE l.id = ?', [(int) $id]);
        if (!$lead) {
            $this->notFound();
        }
        $this->admin('leads/show', [
            'title' => $lead['name'],
            'lead' => $lead,
            'activities' => rows('SELECT a.*, u.name AS user_name FROM lead_activities a LEFT JOIN users u ON u.id = a.user_id WHERE a.lead_id = ? ORDER BY a.created_at DESC, a.id DESC', [$lead['id']]),
            'team' => rows("SELECT id, name FROM users WHERE role != 'client' AND is_active = 1 ORDER BY name"),
        ]);
    }

    public function create(): void
    {
        $this->admin('leads/create', ['title' => 'New lead']);
    }

    public function store(): void
    {
        try {
            $source = in_array(input('source'), ['manual', 'referral', 'phone', 'email', 'social'], true) ? input('source') : 'manual';
            $id = LeadService::create($_POST, $source, Auth::id());
        } catch (InvalidArgumentException $e) {
            $this->failed($e->getMessage(), '/admin/leads/new');
        }
        flash('success', 'Lead created.');
        redirect("/admin/leads/{$id}");
    }

    public function update(string $id): void
    {
        $lead = row('SELECT * FROM leads WHERE id = ?', [(int) $id]);
        if (!$lead) {
            $this->notFound();
        }
        $v = (new Validator($_POST))->date('follow_up_date')->in('status', array_keys(Catalog::LEAD_STATUSES), 'status')->max('notes', 5000, 'Notes');
        if ($v->fails()) {
            $this->failed($v->firstError(), "/admin/leads/{$id}");
        }
        $assigned = input('assigned_to') !== '' ? (int) input('assigned_to') : null;
        q('UPDATE leads SET follow_up_date = ?, assigned_to = ?, notes = ?, updated_at = now_local() WHERE id = ?', [
            input('follow_up_date') ?: null, $assigned, input('notes'), $lead['id'],
        ]);
        if ((int) $lead['assigned_to'] !== (int) $assigned && $assigned) {
            $name = scalar('SELECT name FROM users WHERE id = ?', [$assigned]);
            LeadService::addActivity((int) $lead['id'], "Assigned to {$name}.", Auth::id());
        }
        if (input('status') !== '') {
            LeadService::moveTo((int) $lead['id'], input('status'), Auth::id());
        }
        flash('success', 'Lead updated.');
        redirect("/admin/leads/{$id}");
    }

    /** Kanban drag-and-drop (JSON) or form fallback. */
    public function status(string $id): void
    {
        try {
            LeadService::moveTo((int) $id, input('status'), Auth::id());
        } catch (InvalidArgumentException $e) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
                return;
            }
            $this->failed($e->getMessage(), '/admin/leads');
        }
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            $this->json(['ok' => true]);
            return;
        }
        redirect('/admin/leads');
    }

    public function note(string $id): void
    {
        $body = input('body');
        if ($body === '' || mb_strlen($body) > 2000 || !row('SELECT id FROM leads WHERE id = ?', [(int) $id])) {
            $this->failed('Notes must be between 1 and 2000 characters.', "/admin/leads/{$id}");
        }
        LeadService::addActivity((int) $id, $body, Auth::id());
        q('UPDATE leads SET updated_at = now_local() WHERE id = ?', [(int) $id]);
        redirect("/admin/leads/{$id}#activity");
    }
}

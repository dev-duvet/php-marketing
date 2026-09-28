<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\Catalog;
use App\Services\ProjectService;
use InvalidArgumentException;

final class ProjectController extends Controller
{
    public function index(): void
    {
        [$scope, $params] = Auth::clientScope();
        $status = input('status');
        $filter = isset(Catalog::PROJECT_STATUSES[$status]) ? ' AND p.status = ?' : '';
        $this->admin('projects/index', [
            'title' => 'Projects',
            'status' => $status,
            'projects' => rows(
                "SELECT p.*, c.name AS client_name,
                        (SELECT COUNT(*) FROM content_items WHERE project_id = p.id) AS content_count,
                        (SELECT " . group_concat('u.name', ', ') . " FROM project_members pm JOIN users u ON u.id = pm.user_id WHERE pm.project_id = p.id) AS members
                 FROM projects p LEFT JOIN clients c ON c.id = p.client_id
                 WHERE 1=1{$scope}{$filter} ORDER BY p.status = 'completed', p.due_date",
                $filter ? [...$params, $status] : $params
            ),
        ]);
    }

    public function show(string $id): void
    {
        [$scope, $params] = Auth::clientScope();
        $project = row("SELECT p.*, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE p.id = ?{$scope}", [(int) $id, ...$params]);
        if (!$project) {
            $this->notFound();
        }
        $this->admin('projects/show', [
            'title' => $project['name'],
            'project' => $project,
            'members' => rows('SELECT u.name, u.title FROM project_members pm JOIN users u ON u.id = pm.user_id WHERE pm.project_id = ?', [$project['id']]),
            'items' => rows('SELECT * FROM content_items WHERE project_id = ? ORDER BY publish_at', [$project['id']]),
            'assets' => rows('SELECT * FROM assets WHERE project_id = ? ORDER BY created_at DESC', [$project['id']]),
        ]);
    }

    private function formData(?array $project = null): array
    {
        return [
            'title' => $project ? 'Edit project' : 'New project',
            'project' => $project,
            'clients' => rows('SELECT id, name FROM clients ORDER BY name'),
            'team' => rows("SELECT id, name, title FROM users WHERE role != 'client' AND is_active = 1 ORDER BY name"),
            'memberIds' => $project ? array_map('intval', array_column(rows('SELECT user_id FROM project_members WHERE project_id = ?', [$project['id']]), 'user_id')) : [(int) Auth::id()],
        ];
    }

    public function create(): void
    {
        $this->admin('projects/form', $this->formData());
    }

    public function edit(string $id): void
    {
        $project = row('SELECT * FROM projects WHERE id = ?', [(int) $id]);
        if (!$project) {
            $this->notFound();
        }
        $this->admin('projects/form', $this->formData($project));
    }

    public function store(): void
    {
        $data = $_POST + ['members' => []];
        try {
            $id = ProjectService::save($data);
        } catch (InvalidArgumentException $e) {
            $this->failed($e->getMessage(), '/admin/projects/new');
        }
        flash('success', 'Project created.');
        redirect("/admin/projects/{$id}");
    }

    public function update(string $id): void
    {
        $data = $_POST + ['members' => []];
        try {
            ProjectService::save($data, (int) $id);
        } catch (InvalidArgumentException $e) {
            $this->failed($e->getMessage(), "/admin/projects/{$id}/edit");
        }
        flash('success', 'Project updated.');
        redirect("/admin/projects/{$id}");
    }
}

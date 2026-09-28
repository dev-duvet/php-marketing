<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class ProjectService
{
    public static function validate(array $data): Validator
    {
        return (new Validator($data))
            ->required('name', 'Project name')->max('name', 160, 'Project name')
            ->in('status', array_keys(Catalog::PROJECT_STATUSES), 'status')
            ->date('start_date')->date('due_date');
    }

    public static function save(array $data, ?int $id = null): int
    {
        $validator = self::validate($data);
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->firstError());
        }
        $name = trim((string) $data['name']);
        $clientId = ($data['client_id'] ?? '') !== '' ? (int) $data['client_id'] : null;
        $newClient = trim((string) ($data['new_client'] ?? ''));
        if ($clientId === null && $newClient !== '') {
            q('INSERT INTO clients (name) VALUES (?)', [$newClient]);
            $clientId = (int) db()->lastInsertId();
        }

        $fields = [
            $name,
            $clientId,
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['service_type'] ?? '')),
            ($data['start_date'] ?? '') ?: null,
            ($data['due_date'] ?? '') ?: null,
            $data['status'] ?? 'planning',
            trim((string) ($data['deliverables'] ?? '')),
            max(0, min(100, (int) ($data['progress'] ?? 0))),
            trim((string) ($data['notes'] ?? '')),
            !empty($data['is_public']) ? 1 : 0,
            trim((string) ($data['outcomes'] ?? '')),
        ];

        if ($id === null) {
            q('INSERT INTO projects (name, client_id, description, service_type, start_date, due_date, status, deliverables, progress, notes, is_public, outcomes, slug)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [...$fields, unique_slug('projects', $name)]);
            $id = (int) db()->lastInsertId();
            log_activity('project.created', "Project #{$id} created ({$name})");
        } else {
            if (!row('SELECT id FROM projects WHERE id = ?', [$id])) {
                throw new InvalidArgumentException('Project not found.');
            }
            q('UPDATE projects SET name = ?, client_id = ?, description = ?, service_type = ?, start_date = ?, due_date = ?, status = ?,
               deliverables = ?, progress = ?, notes = ?, is_public = ?, outcomes = ?, updated_at = now_local() WHERE id = ?', [...$fields, $id]);
            log_activity('project.updated', "Project #{$id} updated");
        }

        if (array_key_exists('members', $data)) {
            q('DELETE FROM project_members WHERE project_id = ?', [$id]);
            foreach ((array) $data['members'] as $userId) {
                if (row("SELECT id FROM users WHERE id = ? AND role != 'client'", [(int) $userId])) {
                    q('INSERT INTO project_members (project_id, user_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [$id, (int) $userId]);
                }
            }
        }
        return $id;
    }
}

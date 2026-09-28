<?php
declare(strict_types=1);

use App\Services\ProjectService;

$team = array_map('intval', array_column(rows("SELECT id FROM users WHERE role != 'client'"), 'id'));
$clientUser = (int) scalar("SELECT id FROM users WHERE role = 'client'");

test('a project can be created with a new client and team members', function () use ($team, $clientUser) {
    $id = ProjectService::save([
        'name' => 'Test Project', 'new_client' => 'New Client Co', 'service_type' => 'websites',
        'status' => 'planning', 'progress' => '150', 'start_date' => '2030-01-01', 'due_date' => '2030-02-01',
        'members' => [...$team, $clientUser],
    ]);
    $project = row('SELECT p.*, c.name AS client FROM projects p JOIN clients c ON c.id = p.client_id WHERE p.id = ?', [$id]);
    assert_same('New Client Co', $project['client']);
    assert_same('test-project', $project['slug']);
    assert_same(100, (int) $project['progress'], 'progress is clamped');
    assert_same(count($team), (int) scalar('SELECT COUNT(*) FROM project_members WHERE project_id = ?', [$id]), 'client users cannot be team members');
});

test('project slugs stay unique', function () {
    $a = ProjectService::save(['name' => 'Same Name']);
    $b = ProjectService::save(['name' => 'Same Name']);
    assert_true(scalar('SELECT slug FROM projects WHERE id = ?', [$a]) !== scalar('SELECT slug FROM projects WHERE id = ?', [$b]));
});

test('a project can be updated', function () {
    $id = ProjectService::save(['name' => 'Before', 'status' => 'planning']);
    ProjectService::save(['name' => 'After', 'status' => 'completed', 'progress' => '100'], $id);
    $project = row('SELECT * FROM projects WHERE id = ?', [$id]);
    assert_same('After', $project['name']);
    assert_same('completed', $project['status']);
});

test('invalid projects are rejected', function () {
    assert_throws(fn () => ProjectService::save(['name' => '']));
    assert_throws(fn () => ProjectService::save(['name' => 'x', 'status' => 'nonsense']));
    assert_throws(fn () => ProjectService::save(['name' => 'x'], 999999));
});

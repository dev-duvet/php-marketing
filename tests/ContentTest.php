<?php
declare(strict_types=1);

use App\Services\ContentService;

$producer = (int) scalar('SELECT id FROM users WHERE email = ?', ['team@createza.test']);
$client = (int) scalar('SELECT id FROM users WHERE email = ?', ['client@createza.test']);

test('content can be created with platforms and attached assets', function () use ($producer) {
    $assetId = (int) scalar('SELECT id FROM assets LIMIT 1');
    $id = ContentService::save([
        'title' => 'Test reel', 'format' => 'Reel', 'caption' => 'Hello', 'status' => 'draft',
        'platforms' => ['instagram', 'tiktok', 'myspace'], 'publish_at' => '2030-02-01T10:00', 'asset_ids' => [$assetId],
    ], null, $producer);
    assert_same('draft', scalar('SELECT status FROM content_items WHERE id = ?', [$id]));
    assert_same('2030-02-01 10:00', scalar('SELECT publish_at FROM content_items WHERE id = ?', [$id]));
    assert_same(2, (int) scalar('SELECT COUNT(*) FROM content_platforms WHERE content_id = ?', [$id]), 'unknown platforms are dropped');
    assert_same(1, (int) scalar('SELECT COUNT(*) FROM content_assets WHERE content_id = ?', [$id]));
});

test('content needs a title and at least one platform', function () use ($producer) {
    assert_throws(fn () => ContentService::save(['title' => '', 'platforms' => ['instagram']], null, $producer));
    assert_throws(fn () => ContentService::save(['title' => 'x', 'platforms' => []], null, $producer));
});

test('content can be edited', function () use ($producer) {
    $id = ContentService::save(['title' => 'Draft', 'platforms' => ['youtube']], null, $producer);
    ContentService::save(['title' => 'Renamed', 'platforms' => ['linkedin'], 'status' => 'in_production'], $id, $producer);
    assert_same('Renamed', scalar('SELECT title FROM content_items WHERE id = ?', [$id]));
    assert_same('linkedin', scalar('SELECT platform FROM content_platforms WHERE content_id = ?', [$id]));
});

test('content moves through the approval workflow with versions', function () use ($producer, $client) {
    $id = ContentService::save(['title' => 'Approval test', 'platforms' => ['instagram']], null, $producer);
    ContentService::submit($id, $producer);
    assert_same('awaiting_approval', scalar('SELECT status FROM content_items WHERE id = ?', [$id]));

    assert_throws(fn () => ContentService::review($id, 'changes_requested', $client, ''), 'changes need a note');
    ContentService::review($id, 'changes_requested', $client, 'Shorter please');
    assert_same('in_production', scalar('SELECT status FROM content_items WHERE id = ?', [$id]));
    assert_same(1, (int) scalar('SELECT COUNT(*) FROM content_comments WHERE content_id = ?', [$id]));

    ContentService::submit($id, $producer);
    assert_same(2, (int) scalar('SELECT version FROM content_items WHERE id = ?', [$id]), 'resubmission bumps the version');
    ContentService::review($id, 'approved', $client);
    assert_same('approved', scalar('SELECT status FROM content_items WHERE id = ?', [$id]));
    assert_same(4, (int) scalar('SELECT COUNT(*) FROM content_approvals WHERE content_id = ?', [$id]));
    assert_throws(fn () => ContentService::review($id, 'approved', $client), 'cannot re-review an approved item');
});

test('saving cannot skip straight to awaiting approval', function () use ($producer) {
    $id = ContentService::save(['title' => 'Sneaky', 'platforms' => ['instagram'], 'status' => 'awaiting_approval'], null, $producer);
    assert_same('draft', scalar('SELECT status FROM content_items WHERE id = ?', [$id]));
});

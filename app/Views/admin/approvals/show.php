<?php
use App\Services\Catalog;

$heading = 'Approvals';
$sub = 'Review, comment and approve production content.';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
$actionLabels = ['submitted' => 'Sent for approval', 'approved' => 'Approved', 'changes_requested' => 'Changes requested'];
?>
<?php if (!$item): ?>
  <section class="panel center">
    <span class="success-mark"><?= icon('check') ?></span>
    <h2>You’re all caught up</h2>
    <p class="muted">Nothing is waiting for approval right now.</p>
  </section>
<?php else: ?>
  <?php $preview = $assets[0] ?? null; ?>
  <div class="approval">
    <section class="panel approval__main">
      <div class="preview">
        <?php if (!$preview): ?>
          <div class="preview__empty"><?= icon('image') ?><span>No asset attached</span></div>
        <?php elseif ($preview['type'] === 'video'): ?>
          <video src="<?= e(media_url($preview['path'])) ?>" controls preload="metadata"></video>
        <?php elseif ($preview['type'] === 'image'): ?>
          <img src="<?= e(media_url($preview['path'])) ?>" alt="<?= e($preview['title']) ?>">
        <?php else: ?>
          <a class="preview__empty" href="<?= e(media_url($preview['path'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?><span>Open <?= e($preview['title']) ?></span></a>
        <?php endif; ?>
      </div>
      <div class="approval__meta">
        <div>
          <h2><?= e($item['title']) ?></h2>
          <p class="muted small">
            <?= e($item['format']) ?> · v<?= (int) $item['version'] ?> · <?= e($item['project_name'] ?? 'No project') ?> · Publishing <?= fmt_date($item['publish_at'], 'M j, H:i') ?>
          </p>
          <p class="platform-row"><?php foreach ($platforms as $pl): ?><span class="platform platform--<?= e($pl) ?>" title="<?= e(Catalog::PLATFORMS[$pl]) ?>"><?= icon($pl) ?></span><?php endforeach; ?></p>
        </div>
        <span class="status status--<?= e($item['status']) ?>"><?= e(Catalog::CONTENT_STATUSES[$item['status']]) ?></span>
      </div>
      <?php if ($item['caption']): ?><div class="caption-box"><?= nl2br(e($item['caption'])) ?></div><?php endif; ?>
      <?php if (count($assets) > 1): ?>
        <div class="thumb-row">
          <?php foreach ($assets as $a): ?>
            <a class="thumb" href="/admin/assets/<?= (int) $a['id'] ?>"><?php if ($a['type'] === 'image'): ?><img src="<?= e(media_url($a['path'])) ?>" alt="<?= e($a['title']) ?>"><?php else: ?><span class="thumb__file"><?= icon('file') ?></span><?php endif; ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($item['status'] === 'awaiting_approval'): ?>
        <form class="approval-actions" method="post" action="/admin/approvals/<?= (int) $item['id'] ?>/decide">
          <?= csrf_field() ?>
          <strong>Approval actions</strong>
          <label class="field field--grow"><span class="sr-only">Note (required when requesting changes)</span>
            <input name="note" maxlength="2000" placeholder="Add a note (required when requesting changes)">
          </label>
          <button class="btn btn--ghost" type="submit" name="decision" value="changes_requested"><?= icon('message') ?> Request changes</button>
          <button class="btn btn--primary" type="submit" name="decision" value="approved"><?= icon('check') ?> Approve</button>
        </form>
      <?php elseif (can('admin', 'team')): ?>
        <p class="notice"><a href="/admin/content/<?= (int) $item['id'] ?>/edit">Edit this content</a> and send it for approval to start a new review round.</p>
      <?php endif; ?>
    </section>

    <section class="panel approval__side" data-tabs>
      <div class="tabs" role="tablist">
        <button role="tab" type="button" aria-selected="true" aria-controls="tab-comments" id="t-comments">Comments</button>
        <button role="tab" type="button" aria-selected="false" aria-controls="tab-history" id="t-history">Version history</button>
      </div>
      <div role="tabpanel" id="tab-comments" aria-labelledby="t-comments">
        <ul class="comments" id="comments">
          <?php foreach ($comments as $c): ?>
            <li>
              <span class="avatar avatar--<?= e($c['role'] ?? 'team') ?>"><?= e(mb_substr($c['name'] ?? '?', 0, 1)) ?></span>
              <div>
                <strong><?= e($c['name'] ?? 'Former user') ?></strong> <span class="muted small"><?= time_ago($c['created_at']) ?> · v<?= (int) $c['version'] ?></span>
                <p><?= nl2br(e($c['body'])) ?></p>
              </div>
            </li>
          <?php endforeach; ?>
          <?php if (!$comments): ?><li class="muted">No comments yet.</li><?php endif; ?>
        </ul>
        <form class="comment-form" method="post" action="/admin/approvals/<?= (int) $item['id'] ?>/comment">
          <?= csrf_field() ?>
          <label class="sr-only" for="comment-body">Add a comment</label>
          <input id="comment-body" name="body" required maxlength="2000" placeholder="Add a comment…">
          <button class="icon-btn" type="submit" aria-label="Post comment"><?= icon('send') ?></button>
        </form>
      </div>
      <div role="tabpanel" id="tab-history" aria-labelledby="t-history" hidden>
        <ol class="timeline">
          <?php foreach ($history as $h): ?>
            <li class="timeline__item timeline__item--<?= e($h['action']) ?>">
              <strong><?= e($actionLabels[$h['action']]) ?></strong> · v<?= (int) $h['version'] ?>
              <span class="muted small block"><?= e($h['name'] ?? 'Former user') ?> · <?= fmt_date($h['created_at'], 'M j, H:i') ?></span>
              <?php if ($h['note']): ?><p><?= e($h['note']) ?></p><?php endif; ?>
              <?php if ($h['snapshot'] && ($snap = json_decode($h['snapshot'], true))): ?>
                <details><summary>Snapshot</summary><p class="small"><strong><?= e($snap['title'] ?? '') ?></strong><br><?= e($snap['caption'] ?? '') ?></p></details>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          <?php if (!$history): ?><li class="muted">No approval activity yet.</li><?php endif; ?>
        </ol>
      </div>
    </section>
  </div>

  <?php if (count($queue) > 1): ?>
    <section class="panel">
      <h2>Awaiting approval (<?= count($queue) ?>)</h2>
      <ul class="simple-list">
        <?php foreach ($queue as $q): ?>
          <li class="<?= (int) $q['id'] === (int) $item['id'] ? 'is-current' : '' ?>"><a href="/admin/approvals/<?= (int) $q['id'] ?>"><?= e($q['title']) ?></a><span class="muted"><?= e($q['format']) ?> · <?= fmt_date($q['publish_at'], 'M j') ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
<?php endif; ?>

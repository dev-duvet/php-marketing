<?php
use App\Services\Catalog;

$heading = $asset['title'];
$sub = strtoupper(pathinfo($asset['path'], PATHINFO_EXTENSION)) . ' · ' . human_size((int) $asset['size']) . ' · uploaded ' . fmt_date($asset['created_at']);
$actions = '<a class="btn btn--ghost" href="/admin/assets">' . icon('chevron-left') . ' Library</a> <a class="btn btn--ghost" href="' . e(media_url($asset['path'])) . '" download>' . icon('download') . ' Download</a>';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
$staff = can('admin', 'team');
?>
<div class="dash-grid">
  <section class="panel dash-grid__wide">
    <div class="preview preview--contain">
      <?php if ($asset['type'] === 'image'): ?><img src="<?= e(media_url($asset['path'])) ?>" alt="<?= e($asset['title']) ?>">
      <?php elseif ($asset['type'] === 'video'): ?><video src="<?= e(media_url($asset['path'])) ?>" controls preload="metadata"></video>
      <?php else: ?><a class="preview__empty" href="<?= e(media_url($asset['path'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?><span>Open file</span></a><?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <h2>Details</h2>
    <?php if ($staff): ?>
      <form class="form" method="post" action="/admin/assets/<?= (int) $asset['id'] ?>">
        <?= csrf_field() ?>
        <label class="field"><span>Title</span><input name="title" required maxlength="120" value="<?= e($asset['title']) ?>"></label>
        <label class="field"><span>Project</span>
          <select name="project_id"><option value="">No project</option><?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $asset['project_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field"><span>Tags (comma separated)</span><input name="tags" value="<?= e(implode(', ', $tags)) ?>"></label>
        <label class="field"><span>Show in public gallery as</span>
          <select name="public_category">
            <option value="">Not in public gallery</option>
            <?php foreach (Catalog::GALLERY_CATEGORIES as $k => $l): ?><option value="<?= $k ?>" <?= $asset['public_category'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
          </select>
        </label>
        <button class="btn btn--navy" type="submit">Save details</button>
      </form>
    <?php else: ?>
      <dl class="facts facts--stack">
        <div><dt>Project</dt><dd><?= e($asset['project_name'] ?? '—') ?></dd></div>
        <div><dt>Tags</dt><dd><?= e(implode(', ', $tags) ?: '—') ?></dd></div>
      </dl>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Used in</h2>
    <ul class="simple-list">
      <?php foreach ($usedIn as $c): ?><li><a href="<?= $staff ? "/admin/content/{$c['id']}/edit" : "/admin/approvals/{$c['id']}" ?>"><?= e($c['title']) ?></a><span class="status status--<?= e($c['status']) ?>"><?= e(Catalog::CONTENT_STATUSES[$c['status']]) ?></span></li><?php endforeach; ?>
      <?php if (!$usedIn): ?><li class="muted">Not attached to any content yet.</li><?php endif; ?>
    </ul>
    <?php if ($staff && $contentItems): ?>
      <form class="inline-form" method="post" action="/admin/assets/<?= (int) $asset['id'] ?>/attach">
        <?= csrf_field() ?>
        <label class="sr-only" for="content_id">Attach to content</label>
        <select id="content_id" name="content_id" required>
          <option value="">Attach to content…</option>
          <?php foreach ($contentItems as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--ghost btn--sm" type="submit"><?= icon('link') ?> Attach</button>
      </form>
    <?php endif; ?>
    <?php if ($staff): ?>
      <hr>
      <button class="btn btn--danger-text" type="button" data-open-dialog="delete-asset"><?= icon('trash') ?> Delete asset</button>
    <?php endif; ?>
  </section>
</div>

<?php if ($staff): ?>
  <dialog class="dialog" id="delete-asset">
    <form method="post" action="/admin/assets/<?= (int) $asset['id'] ?>/delete">
      <?= csrf_field() ?>
      <h2>Delete this asset?</h2>
      <p class="muted">“<?= e($asset['title']) ?>” will be removed from the library and from <?= count($usedIn) ?> content item(s). This can’t be undone.</p>
      <label class="check"><input type="checkbox" name="confirm" value="yes" required> I understand, delete it</label>
      <div class="form-actions">
        <button class="btn btn--ghost" type="button" data-close-dialog>Cancel</button>
        <button class="btn btn--danger" type="submit">Delete asset</button>
      </div>
    </form>
  </dialog>
<?php endif; ?>

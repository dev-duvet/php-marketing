<?php
use App\Services\Catalog;

$v = fn (string $key, string $default = '') => old($key, $item[$key] ?? $default);
$publish = $v('publish_at');
$publishValue = $publish ? date('Y-m-d\TH:i', strtotime($publish)) : '';
$attachedIds = array_map('intval', array_column($attached, 'id'));
$heading = $item ? 'Edit content' : 'Content editor';
$sub = $item ? 'Version ' . (int) $item['version'] . ' · ' . Catalog::CONTENT_STATUSES[$item['status']] : 'Create and manage your social content.';
$actions = $item && in_array($item['status'], ['awaiting_approval', 'approved', 'scheduled', 'published'], true)
    ? '<a class="btn btn--ghost" href="/admin/approvals/' . (int) $item['id'] . '">' . icon('check-circle') . ' Approval thread</a>' : '';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<form class="panel form content-form" method="post" enctype="multipart/form-data" action="<?= $item ? '/admin/content/' . (int) $item['id'] : '/admin/content' ?>">
  <?= csrf_field() ?>
  <h2><?= $item ? e($item['title']) : 'Create new content' ?></h2>
  <div class="form-grid">
    <label class="field field--full"><span>Title <em>*</em></span><input name="title" required maxlength="160" placeholder="Enter a content title…" value="<?= e($v('title')) ?>"></label>

    <label class="field"><span>Client / Project</span>
      <select name="project_id">
        <option value="">Select client or project…</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (string) $v('project_id') === (string) $p['id'] ? 'selected' : '' ?>><?= e(($p['client_name'] ? $p['client_name'] . ' — ' : '') . $p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <fieldset class="field">
      <legend>Platforms <em>*</em></legend>
      <div class="check-grid">
        <?php foreach (Catalog::PLATFORMS as $key => $label): ?>
          <label class="check"><input type="checkbox" name="platforms[]" value="<?= $key ?>" <?= in_array($key, $platforms, true) ? 'checked' : '' ?>> <?= $label ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <label class="field"><span>Format</span>
      <select name="format"><?php foreach (Catalog::FORMATS as $f): ?><option <?= $v('format', 'Photo') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select>
    </label>

    <label class="field"><span>Assigned team member</span>
      <select name="assigned_to">
        <option value="">Unassigned</option>
        <?php foreach ($team as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (string) $v('assigned_to', (string) user()['id']) === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
      </select>
    </label>

    <label class="field field--full">
      <span class="field__row">Caption <span class="muted small" data-counter-for="caption">0/2200</span></span>
      <textarea id="caption" name="caption" rows="5" maxlength="2200" placeholder="Write your caption here…" data-counter><?= e($v('caption')) ?></textarea>
    </label>

    <div class="field">
      <span>Attach assets</span>
      <label class="dropzone" data-dropzone>
        <?= icon('upload') ?>
        <strong>Drag and drop files here</strong>
        <span class="muted small">or click to browse</span>
        <input type="file" name="uploads[]" multiple accept="image/*,video/mp4,video/webm,video/quicktime,application/pdf,.psd,.ai">
        <span class="dropzone__files muted small" data-dropzone-files></span>
      </label>
      <span class="muted small">Images, videos or design files (max <?= e(env('UPLOAD_MAX_MB', '50')) ?>MB each)</span>
    </div>

    <div class="field">
      <label class="field"><span>Publish date &amp; time</span><input type="datetime-local" name="publish_at" value="<?= e($publishValue) ?>"></label>
      <label class="field"><span>Status</span>
        <select name="status">
          <?php foreach (Catalog::CONTENT_STATUSES as $key => $label): ?>
            <?php if ($key === 'awaiting_approval' && ($item['status'] ?? '') !== 'awaiting_approval') continue; ?>
            <option value="<?= $key ?>" <?= $v('status', 'draft') === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <details class="field field--full library-pick" <?= $attached ? 'open' : '' ?>>
      <summary>Choose from asset library (<?= count($attachedIds) ?> attached)</summary>
      <div class="pick-grid">
        <?php foreach ($library as $a): ?>
          <label class="pick">
            <input type="checkbox" name="asset_ids[]" value="<?= (int) $a['id'] ?>" <?= in_array((int) $a['id'], $attachedIds, true) ? 'checked' : '' ?>>
            <?php if ($a['type'] === 'image'): ?><img src="<?= e(media_url($a['path'])) ?>" alt="" loading="lazy"><?php else: ?><span class="thumb__file"><?= icon('file') ?></span><?php endif; ?>
            <span class="pick__title"><?= e($a['title']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="muted small">Unticking an asset here does not detach it; manage attachments from the asset’s page.</p>
    </details>

    <label class="field field--full"><span>Internal notes</span><textarea name="internal_notes" rows="3" placeholder="Only visible to the team"><?= e($v('internal_notes')) ?></textarea></label>
  </div>

  <div class="form-actions">
    <?php if ($item): ?>
      <button class="btn btn--danger-text" type="button" data-open-dialog="delete-content"><?= icon('trash') ?> Delete</button>
    <?php endif; ?>
    <span class="spacer"></span>
    <button class="btn btn--ghost" type="submit" name="action" value="save">Save draft</button>
    <button class="btn btn--primary" type="submit" name="action" value="submit"><?= icon('send') ?> Send for approval</button>
  </div>
</form>

<?php if ($item): ?>
  <dialog class="dialog" id="delete-content">
    <form method="post" action="/admin/content/<?= (int) $item['id'] ?>/delete">
      <?= csrf_field() ?>
      <input type="hidden" name="confirm" value="yes">
      <h2>Delete “<?= e($item['title']) ?>”?</h2>
      <p class="muted">This removes the content item, its comments and approval history. Attached assets stay in the library.</p>
      <div class="form-actions">
        <button class="btn btn--ghost" type="button" data-close-dialog>Cancel</button>
        <button class="btn btn--danger" type="submit">Delete content</button>
      </div>
    </form>
  </dialog>
<?php endif; ?>

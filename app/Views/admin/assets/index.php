<?php
$heading = 'Asset library';
$sub = 'Store, organise and reuse your media assets.';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
$types = ['' => 'All', 'image' => 'Photos', 'video' => 'Videos', 'design' => 'Designs', 'document' => 'Documents'];
$query = fn (array $change) => '/admin/assets?' . http_build_query(array_filter($change + $filters, fn ($v) => $v !== ''));
?>
<?php if (can('admin', 'team')): ?>
  <details class="panel upload-panel" <?= $assets ? '' : 'open' ?>>
    <summary class="btn btn--primary btn--sm"><?= icon('upload') ?> Upload assets</summary>
    <form class="form upload-form" method="post" action="/admin/assets" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label class="dropzone" data-dropzone>
        <?= icon('upload') ?>
        <strong>Drag and drop files here</strong>
        <span class="muted small">JPG, PNG, WEBP, GIF, MP4, WEBM, MOV, PDF, PSD or AI · max <?= e(env('UPLOAD_MAX_MB', '50')) ?>MB each</span>
        <input type="file" name="files[]" multiple required accept="image/*,video/mp4,video/webm,video/quicktime,application/pdf,.psd,.ai">
        <span class="dropzone__files muted small" data-dropzone-files></span>
      </label>
      <div class="form-grid">
        <label class="field"><span>Project</span>
          <select name="project_id"><option value="">No project</option><?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field"><span>Tags (comma separated)</span><input name="tags" maxlength="200" placeholder="studio, portrait"></label>
      </div>
      <button class="btn btn--primary" type="submit">Upload</button>
    </form>
  </details>
<?php endif; ?>

<section class="panel">
  <form class="toolbar" method="get" action="/admin/assets">
    <label class="search-field"><?= icon('search') ?><span class="sr-only">Search assets</span><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search assets…"></label>
    <label class="sr-only" for="project-filter">Project</label>
    <select id="project-filter" name="project" data-autosubmit>
      <option value="">All projects</option>
      <?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $filters['project'] === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
    </select>
    <label class="sr-only" for="tag-filter">Tag</label>
    <select id="tag-filter" name="tag" data-autosubmit>
      <option value="">All tags</option>
      <?php foreach ($tags as $t): ?><option <?= $filters['tag'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
    </select>
    <input type="hidden" name="type" value="<?= e($filters['type']) ?>">
    <button class="btn btn--ghost btn--sm" type="submit">Apply</button>
  </form>
  <nav class="tabs tabs--links" aria-label="File type">
    <?php foreach ($types as $key => $label): ?>
      <a href="<?= e($query(['type' => $key])) ?>" class="<?= $filters['type'] === $key ? 'is-active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <span class="spacer"></span><span class="muted small"><?= count($assets) ?> assets</span>
  </nav>
  <div class="asset-grid">
    <?php foreach ($assets as $a): ?>
      <a class="asset" href="/admin/assets/<?= (int) $a['id'] ?>">
        <span class="asset__media">
          <?php if ($a['type'] === 'image'): ?><img src="<?= e(media_url($a['path'])) ?>" alt="" loading="lazy">
          <?php elseif ($a['type'] === 'video'): ?><video src="<?= e(media_url($a['path'])) ?>" muted preload="metadata"></video><span class="media-card__play"><?= icon('play') ?></span>
          <?php else: ?><span class="thumb__file"><?= icon('file') ?></span><?php endif; ?>
        </span>
        <strong class="asset__title"><?= e($a['title']) ?></strong>
        <span class="muted small"><?= e(strtoupper(pathinfo($a['path'], PATHINFO_EXTENSION))) ?> · <?= human_size((int) $a['size']) ?><?= $a['project_name'] ? ' · ' . e($a['project_name']) : '' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$assets): ?><p class="muted">No assets match these filters.</p><?php endif; ?>
</section>

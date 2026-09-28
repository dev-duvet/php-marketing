<?php
use App\Services\Catalog;

$heading = $project['name'];
$sub = ($project['client_name'] ?? 'No client') . ' · ' . (Catalog::serviceName($project['service_type']) ?: 'No service set');
$actions = can('admin', 'team') ? '<a class="btn btn--ghost" href="/admin/projects/' . (int) $project['id'] . '/edit">' . icon('edit') . ' Edit project</a>' : '';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<div class="dash-grid">
  <section class="panel dash-grid__wide">
    <div class="panel__head">
      <h2>Overview</h2>
      <span class="status status--<?= e($project['status']) ?>"><?= e(Catalog::PROJECT_STATUSES[$project['status']]) ?></span>
    </div>
    <p><?= nl2br(e($project['description'] ?: 'No description yet.')) ?></p>
    <dl class="facts">
      <div><dt>Start</dt><dd><?= fmt_date($project['start_date']) ?></dd></div>
      <div><dt>Due</dt><dd><?= fmt_date($project['due_date']) ?></dd></div>
      <div><dt>Progress</dt><dd><span class="progress"><span style="width: <?= (int) $project['progress'] ?>%"></span></span> <?= (int) $project['progress'] ?>%</dd></div>
      <div><dt>Public case study</dt><dd><?= $project['is_public'] ? '<a href="/work/' . e($project['slug']) . '" target="_blank" rel="noopener">Published</a>' : 'Hidden' ?></dd></div>
    </dl>
  </section>
  <section class="panel">
    <h2>Team</h2>
    <ul class="simple-list">
      <?php foreach ($members as $m): ?><li><span><?= e($m['name']) ?></span><span class="muted"><?= e($m['title']) ?></span></li><?php endforeach; ?>
      <?php if (!$members): ?><li class="muted">No team members assigned.</li><?php endif; ?>
    </ul>
  </section>
  <section class="panel">
    <h2>Deliverables</h2>
    <ul class="ticks"><?php foreach (array_filter(explode("\n", (string) $project['deliverables'])) as $d): ?><li><?= e(trim($d)) ?></li><?php endforeach; ?></ul>
  </section>
  <?php if (can('admin', 'team') && $project['notes']): ?>
    <section class="panel"><h2>Notes</h2><p><?= nl2br(e($project['notes'])) ?></p></section>
  <?php endif; ?>
  <section class="panel dash-grid__wide">
    <div class="panel__head"><h2>Content</h2><?php if (can('admin', 'team')): ?><a class="link-arrow" href="/admin/content/new">Add content</a><?php endif; ?></div>
    <ul class="simple-list">
      <?php foreach ($items as $c): ?>
        <li><a href="<?= can('admin', 'team') ? "/admin/content/{$c['id']}/edit" : "/admin/approvals/{$c['id']}" ?>"><?= e($c['title']) ?></a><span class="status status--<?= e($c['status']) ?>"><?= e(Catalog::CONTENT_STATUSES[$c['status']]) ?></span></li>
      <?php endforeach; ?>
      <?php if (!$items): ?><li class="muted">No content for this project yet.</li><?php endif; ?>
    </ul>
  </section>
  <section class="panel dash-grid__wide">
    <div class="panel__head"><h2>Assets</h2><a class="link-arrow" href="/admin/assets?project=<?= (int) $project['id'] ?>">Open in library</a></div>
    <div class="thumb-grid thumb-grid--wide">
      <?php foreach ($assets as $a): ?>
        <a class="thumb" href="/admin/assets/<?= (int) $a['id'] ?>"><?php if ($a['type'] === 'image'): ?><img src="<?= e(media_url($a['path'])) ?>" alt="<?= e($a['title']) ?>" loading="lazy"><?php else: ?><span class="thumb__file"><?= icon('file') ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </div>
    <?php if (!$assets): ?><p class="muted">No assets attached yet.</p><?php endif; ?>
  </section>
</div>

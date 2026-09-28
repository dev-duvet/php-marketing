<?php
use App\Services\Catalog;

$week = $grid;
$prev = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$next = date('Y-m-d', strtotime($weekStart . ' +7 days'));
$heading = 'Content calendar';
$sub = 'Plan, produce and schedule content across every platform.';
$actions = can('admin', 'team') ? '<a class="btn btn--primary" href="/admin/content/new">' . icon('plus') . ' New content</a>' : '';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<section class="panel">
  <div class="panel__head">
    <div class="week-nav">
      <a class="icon-btn" href="/admin/content?week=<?= $prev ?>" aria-label="Previous week"><?= icon('chevron-left') ?></a>
      <a class="icon-btn" href="/admin/content?week=<?= $next ?>" aria-label="Next week"><?= icon('chevron-right') ?></a>
      <h2><?= date('M j', strtotime($weekStart)) ?> – <?= date('M j, Y', strtotime($weekStart . ' +6 days')) ?></h2>
    </div>
    <a class="btn btn--ghost btn--sm" href="/admin/content">This week</a>
  </div>
  <?php require BASE_PATH . '/app/Views/admin/partials/week.php'; ?>
  <ul class="legend legend--status">
    <?php foreach (Catalog::CONTENT_STATUSES as $key => $label): ?><li><i class="slot slot--<?= $key ?> legend__swatch"></i><?= $label ?></li><?php endforeach; ?>
  </ul>
</section>

<section class="panel">
  <div class="panel__head">
    <h2>All content</h2>
    <nav class="filters filters--sm" aria-label="Filter by status">
      <a class="filter <?= $status === '' ? 'is-active' : '' ?>" href="/admin/content?week=<?= $weekStart ?>">All</a>
      <?php foreach (Catalog::CONTENT_STATUSES as $key => $label): ?>
        <a class="filter <?= $status === $key ? 'is-active' : '' ?>" href="/admin/content?status=<?= $key ?>&week=<?= $weekStart ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="table-scroll">
    <table class="table">
      <thead><tr><th>Title</th><th>Platforms</th><th>Format</th><th>Publish</th><th>Assigned</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <a href="<?= can('admin', 'team') ? "/admin/content/{$item['id']}/edit" : "/admin/approvals/{$item['id']}" ?>"><strong><?= e($item['title']) ?></strong></a>
              <span class="muted small block"><?= e($item['project_name'] ?? 'No project') ?></span>
            </td>
            <td class="platform-row"><?php foreach (array_filter(explode(',', (string) $item['platforms'])) as $pl): ?><span class="platform platform--<?= e($pl) ?>" title="<?= e(Catalog::PLATFORMS[$pl] ?? $pl) ?>"><?= icon($pl) ?></span><?php endforeach; ?></td>
            <td><?= e($item['format']) ?></td>
            <td><?= fmt_date($item['publish_at'], 'M j, H:i') ?></td>
            <td class="muted"><?= e($item['assignee'] ?? '—') ?></td>
            <td><span class="status status--<?= e($item['status']) ?>"><?= e(Catalog::CONTENT_STATUSES[$item['status']]) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="6" class="muted">No content matches this filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

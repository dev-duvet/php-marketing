<?php
use App\Services\Catalog;

$heading = 'Projects';
$sub = 'Track client work from planning to handover.';
$actions = can('admin', 'team') ? '<a class="btn btn--primary" href="/admin/projects/new">' . icon('plus') . ' New project</a>' : '';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<section class="panel">
  <nav class="filters filters--sm" aria-label="Filter by status">
    <a class="filter <?= $status === '' ? 'is-active' : '' ?>" href="/admin/projects">All</a>
    <?php foreach (Catalog::PROJECT_STATUSES as $key => $label): ?>
      <a class="filter <?= $status === $key ? 'is-active' : '' ?>" href="/admin/projects?status=<?= $key ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="table-scroll">
    <table class="table">
      <thead><tr><th>Project</th><th>Client</th><th>Service</th><th>Team</th><th>Progress</th><th>Due</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td><a href="/admin/projects/<?= (int) $p['id'] ?>"><strong><?= e($p['name']) ?></strong></a><span class="muted small block"><?= (int) $p['content_count'] ?> content items</span></td>
            <td class="muted"><?= e($p['client_name'] ?? '—') ?></td>
            <td><?= e(Catalog::serviceName($p['service_type']) ?: '—') ?></td>
            <td class="muted small"><?= e($p['members'] ?? '—') ?></td>
            <td><span class="progress"><span style="width: <?= (int) $p['progress'] ?>%"></span></span> <span class="small muted"><?= (int) $p['progress'] ?>%</span></td>
            <td><?= fmt_date($p['due_date'], 'M j, Y') ?></td>
            <td><span class="status status--<?= e($p['status']) ?>"><?= e(Catalog::PROJECT_STATUSES[$p['status']]) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$projects): ?><tr><td colspan="7" class="muted">No projects yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

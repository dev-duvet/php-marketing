<?php
use App\Services\Catalog;

$heading = 'Analytics';
$sub = 'Manual monthly figures for the MVP. Seeded values are demo data — replace them with numbers from each platform’s insights.';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
$maxVolume = max(1, ...array_map('intval', array_column($volume, 'n') ?: [0]));
?>
<div class="stat-grid">
  <div class="stat"><span class="stat__icon stat__icon--green"><?= icon('check-circle') ?></span><span><span class="stat__label">Content published (all time)</span><strong class="stat__value"><?= $published ?></strong></span></div>
  <div class="stat"><span class="stat__icon stat__icon--blue"><?= icon('users') ?></span><span><span class="stat__label">Reach · <?= date('M Y', strtotime($period . '-01')) ?></span><strong class="stat__value"><?= number_format((int) ($totals['reach'] ?? 0)) ?></strong></span></div>
  <div class="stat"><span class="stat__icon stat__icon--gold"><?= icon('chart') ?></span><span><span class="stat__label">Avg. engagement rate</span><strong class="stat__value"><?= number_format((float) ($totals['engagement'] ?? 0), 1) ?>%</strong></span></div>
  <div class="stat"><span class="stat__icon stat__icon--red"><?= icon('folder') ?></span><span><span class="stat__label">Project completion rate</span><strong class="stat__value"><?= $completion ?>%</strong></span></div>
</div>

<div class="dash-grid">
  <section class="panel dash-grid__wide">
    <div class="panel__head"><h2>Platform reach</h2><span class="muted small">Last 6 months</span></div>
    <?php require BASE_PATH . '/app/Views/admin/partials/chart.php'; ?>
  </section>

  <section class="panel">
    <h2>Best-performing formats</h2>
    <ul class="simple-list">
      <?php foreach ($formats as $f): ?><li><span><?= e($f['top_format']) ?></span><span class="muted">top on <?= (int) $f['n'] ?> platform-months</span></li><?php endforeach; ?>
      <?php if (!$formats): ?><li class="muted">No data yet.</li><?php endif; ?>
    </ul>
  </section>

  <section class="panel">
    <h2>Monthly content volume</h2>
    <ul class="bars">
      <?php foreach (array_reverse($volume) as $row): ?>
        <li><span><?= date('M Y', strtotime($row['month'] . '-01')) ?></span><span class="bars__track"><span style="width: <?= round((int) $row['n'] / $maxVolume * 100) ?>%"></span></span><strong><?= (int) $row['n'] ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <p class="muted small">Counted from scheduled content in the calendar.</p>
  </section>

  <section class="panel dash-grid__wide">
    <div class="panel__head">
      <h2>Edit monthly figures</h2>
      <form method="get" action="/admin/analytics" class="inline-form">
        <label class="sr-only" for="period">Month</label>
        <input id="period" type="month" name="period" value="<?= e($period) ?>">
        <button class="btn btn--ghost btn--sm" type="submit">Load</button>
      </form>
    </div>
    <form method="post" action="/admin/analytics">
      <?= csrf_field() ?>
      <input type="hidden" name="period" value="<?= e($period) ?>">
      <div class="table-scroll">
        <table class="table table--inputs">
          <thead><tr><th>Platform</th><th>Posts published</th><th>Reach</th><th>Engagement rate (%)</th><th>Top format</th></tr></thead>
          <tbody>
            <?php foreach (Catalog::PLATFORMS as $key => $label): $m = $metrics[$key] ?? []; ?>
              <tr>
                <th scope="row"><span class="platform platform--<?= $key ?>"><?= icon($key) ?></span> <?= $label ?></th>
                <td><input type="number" min="0" name="metrics[<?= $key ?>][posts_published]" value="<?= (int) ($m['posts_published'] ?? 0) ?>" aria-label="<?= $label ?> posts published"></td>
                <td><input type="number" min="0" name="metrics[<?= $key ?>][reach]" value="<?= (int) ($m['reach'] ?? 0) ?>" aria-label="<?= $label ?> reach"></td>
                <td><input type="number" min="0" max="100" step="0.1" name="metrics[<?= $key ?>][engagement_rate]" value="<?= e($m['engagement_rate'] ?? 0) ?>" aria-label="<?= $label ?> engagement rate"></td>
                <td><select name="metrics[<?= $key ?>][top_format]" aria-label="<?= $label ?> top format"><option value="">—</option><?php foreach (Catalog::FORMATS as $f): ?><option <?= ($m['top_format'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="form-actions"><span class="spacer"></span><button class="btn btn--primary" type="submit">Save <?= date('F Y', strtotime($period . '-01')) ?></button></div>
    </form>
  </section>
</div>

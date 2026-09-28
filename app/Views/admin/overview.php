<?php
use App\Services\Catalog;

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$first = explode(' ', user()['name'])[0];
$cards = [
    ['Drafts', $stats['drafts'], 'file', 'blue', '/admin/content?status=draft'],
    ['Awaiting approval', $stats['awaiting'], 'clock', 'gold', '/admin/approvals'],
    ['Scheduled this week', $stats['scheduled'], 'calendar', 'green', '/admin/content'],
    ['Active projects', $stats['projects'], 'folder', 'red', '/admin/projects'],
];
?>
<div class="page-head">
  <div>
    <h1><?= $greeting ?>, <?= e($first) ?>.</h1>
    <p class="muted">Here’s what’s happening with your content and projects.</p>
  </div>
</div>

<div class="stat-grid">
  <?php foreach ($cards as [$label, $value, $ico, $tone, $href]): ?>
    <a class="stat" href="<?= $href ?>">
      <span class="stat__icon stat__icon--<?= $tone ?>"><?= icon($ico) ?></span>
      <span><span class="stat__label"><?= $label ?></span><strong class="stat__value"><?= (int) $value ?></strong></span>
      <?= icon('chevron-right', 'icon stat__chev') ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="dash-grid">
  <section class="panel dash-grid__wide">
    <div class="panel__head">
      <h2>Content calendar</h2>
      <span class="muted small"><?= date('M j', strtotime($weekStart)) ?> – <?= date('M j, Y', strtotime($weekStart . ' +6 days')) ?></span>
      <a class="link-arrow" href="/admin/content">View calendar</a>
    </div>
    <?php require BASE_PATH . '/app/Views/admin/partials/week.php'; ?>
  </section>

  <section class="panel">
    <div class="panel__head"><h2>Needs your attention</h2><a class="link-arrow" href="/admin/approvals">View all</a></div>
    <?php if (!$attention): ?><p class="muted">Nothing waiting on you. Nice.</p><?php endif; ?>
    <ul class="attention">
      <?php foreach ($attention as $i => $item): ?>
        <li>
          <img src="<?= e(media_url($item['thumb'])) ?>" alt="" width="72" height="72" loading="lazy">
          <div>
            <span class="chip <?= $i === 0 ? 'chip--red' : 'chip--gold' ?>"><?= $i === 0 ? 'High priority' : 'Needs review' ?></span>
            <strong><?= e($item['title']) ?></strong>
            <span class="muted small">Publishing <?= fmt_date($item['publish_at'], 'M j, H:i') ?></span>
            <a class="btn btn--navy btn--xs" href="/admin/approvals/<?= (int) $item['id'] ?>">Review</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="panel dash-grid__wide">
    <div class="panel__head"><h2>Active projects</h2><a class="link-arrow" href="/admin/projects">View all</a></div>
    <div class="table-scroll">
      <table class="table">
        <thead><tr><th>Project</th><th>Client</th><th>Progress</th><th>Due date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($projects as $p): ?>
            <tr>
              <td><a href="/admin/projects/<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a></td>
              <td class="muted"><?= e($p['client_name'] ?? '—') ?></td>
              <td><span class="progress"><span style="width: <?= (int) $p['progress'] ?>%"></span></span> <span class="small muted"><?= (int) $p['progress'] ?>%</span></td>
              <td><?= fmt_date($p['due_date'], 'M j, Y') ?></td>
              <td><span class="status status--<?= e($p['status']) ?>"><?= e(Catalog::PROJECT_STATUSES[$p['status']]) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <div class="panel__head"><h2>Recent assets</h2><a class="link-arrow" href="/admin/assets">View all</a></div>
    <div class="thumb-grid">
      <?php foreach ($assets as $a): ?>
        <a href="/admin/assets/<?= (int) $a['id'] ?>" class="thumb">
          <?php if ($a['type'] === 'image'): ?><img src="<?= e(media_url($a['path'])) ?>" alt="<?= e($a['title']) ?>" loading="lazy"><?php else: ?><span class="thumb__file"><?= icon('file') ?></span><?php endif; ?>
          <span class="thumb__tag"><?= e(ucfirst($a['type'])) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($pipeline !== null): ?>
    <section class="panel dash-grid__wide">
      <div class="panel__head"><h2>New leads</h2><a class="link-arrow" href="/admin/leads">View all</a></div>
      <ol class="pipeline">
        <?php foreach (['new', 'contacted', 'discovery', 'proposal', 'won'] as $status): ?>
          <li class="pipeline__step pipeline__step--<?= $status ?>"><span><?= Catalog::LEAD_STATUSES[$status] ?></span><strong><?= (int) ($pipeline[$status] ?? 0) ?></strong></li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>

  <section class="panel <?= $pipeline === null ? 'dash-grid__wide' : '' ?>">
    <div class="panel__head"><h2>Content performance</h2><span class="muted small">Monthly reach</span></div>
    <?php require BASE_PATH . '/app/Views/admin/partials/chart.php'; ?>
  </section>

  <?php if ($orders || $registrations): ?>
    <section class="panel dash-grid__wide">
      <div class="panel__head"><h2>Store & events</h2><a class="link-arrow" href="/admin/settings#orders">Manage</a></div>
      <div class="two-col">
        <div>
          <h3 class="small-title">Latest orders</h3>
          <ul class="simple-list">
            <?php foreach ($orders as $o): ?><li><span><?= e($o['reference']) ?> · <?= e($o['customer_name']) ?></span><span><?= money((int) $o['subtotal_cents']) ?></span></li><?php endforeach; ?>
            <?php if (!$orders): ?><li class="muted">No orders yet.</li><?php endif; ?>
          </ul>
        </div>
        <div>
          <h3 class="small-title">Latest registrations</h3>
          <ul class="simple-list">
            <?php foreach ($registrations as $r): ?><li><span><?= e($r['name']) ?></span><span class="muted"><?= e($r['event_title']) ?></span></li><?php endforeach; ?>
            <?php if (!$registrations): ?><li class="muted">No registrations yet.</li><?php endif; ?>
          </ul>
        </div>
      </div>
    </section>
  <?php endif; ?>
</div>

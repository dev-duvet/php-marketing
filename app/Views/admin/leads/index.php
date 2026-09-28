<?php
use App\Services\Catalog;

$heading = 'Leads';
$sub = 'Manage your sales pipeline and enquiries. Drag cards between columns to update their stage.';
$actions = '<a class="btn btn--primary" href="/admin/leads/new">' . icon('plus') . ' New lead</a>';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<form class="toolbar" method="get" action="/admin/leads">
  <label class="search-field"><?= icon('search') ?><span class="sr-only">Search leads</span><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search leads…"></label>
  <label class="sr-only" for="assigned">Assigned to</label>
  <select id="assigned" name="assigned" data-autosubmit>
    <option value="">Everyone</option>
    <?php foreach ($team as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $assigned === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn--ghost btn--sm" type="submit">Filter</button>
</form>

<div class="kanban" data-kanban>
  <?php foreach ($columns as $status => $leads): ?>
    <section class="kanban__col kanban__col--<?= $status ?>" data-status="<?= $status ?>" aria-label="<?= Catalog::LEAD_STATUSES[$status] ?>">
      <header class="kanban__head"><h2><?= Catalog::LEAD_STATUSES[$status] ?></h2><span class="count" data-count><?= count($leads) ?></span></header>
      <div class="kanban__list" data-dropzone-list>
        <?php foreach ($leads as $lead): ?>
          <article class="lead-card" draggable="true" data-lead="<?= (int) $lead['id'] ?>">
            <a href="/admin/leads/<?= (int) $lead['id'] ?>"><strong><?= e($lead['name']) ?></strong></a>
            <span class="muted small"><?= e(Catalog::serviceName($lead['service'])) ?></span>
            <span class="lead-card__meta">
              <span title="Activity"><?= icon('message') ?> <?= (int) $lead['activity_count'] ?></span>
              <?php if ($lead['follow_up_date']): ?><span title="Follow up"><?= icon('calendar') ?> <?= fmt_date($lead['follow_up_date'], 'M j') ?></span><?php endif; ?>
            </span>
            <form class="lead-card__move" method="post" action="/admin/leads/<?= (int) $lead['id'] ?>/status">
              <?= csrf_field() ?>
              <label class="sr-only" for="move-<?= (int) $lead['id'] ?>">Move <?= e($lead['name']) ?> to</label>
              <select id="move-<?= (int) $lead['id'] ?>" name="status" data-autosubmit>
                <?php foreach (Catalog::LEAD_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $k === $status ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
              </select>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>

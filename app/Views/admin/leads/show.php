<?php
use App\Services\Catalog;

$heading = $lead['name'];
$sub = 'Received ' . fmt_date($lead['created_at']) . ' via ' . $lead['source'];
$actions = '<a class="btn btn--ghost" href="/admin/leads">' . icon('chevron-left') . ' Pipeline</a>';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<div class="dash-grid">
  <section class="panel">
    <div class="panel__head"><h2>Enquiry details</h2><span class="status status--<?= e($lead['status']) ?>"><?= e(Catalog::LEAD_STATUSES[$lead['status']]) ?></span></div>
    <dl class="facts facts--stack">
      <div><dt>Email</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd></div>
      <div><dt>Phone</dt><dd><?= $lead['phone'] ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $lead['phone'])) . '">' . e($lead['phone']) . '</a>' : '—' ?></dd></div>
      <div><dt>Organisation</dt><dd><?= e($lead['organisation'] ?: '—') ?></dd></div>
      <div><dt>Service interest</dt><dd><?= e(Catalog::serviceName($lead['service'])) ?></dd></div>
      <div><dt>Budget</dt><dd><?= e($lead['budget'] ?: '—') ?></dd></div>
      <div><dt>Preferred start</dt><dd><?= fmt_date($lead['preferred_start']) ?></dd></div>
    </dl>
    <h3 class="small-title">Description</h3>
    <p><?= nl2br(e($lead['details'])) ?></p>
  </section>

  <section class="panel">
    <h2>Manage</h2>
    <form class="form" method="post" action="/admin/leads/<?= (int) $lead['id'] ?>">
      <?= csrf_field() ?>
      <label class="field"><span>Stage</span>
        <select name="status"><?php foreach (Catalog::LEAD_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $lead['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
      </label>
      <label class="field"><span>Assigned to</span>
        <select name="assigned_to">
          <option value="">Unassigned</option>
          <?php foreach ($team as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $lead['assigned_to'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>Follow-up date</span><input type="date" name="follow_up_date" value="<?= e($lead['follow_up_date']) ?>"></label>
      <label class="field"><span>Notes</span><textarea name="notes" rows="4"><?= e($lead['notes']) ?></textarea></label>
      <button class="btn btn--navy" type="submit">Save</button>
    </form>
  </section>

  <section class="panel dash-grid__wide" id="activity">
    <h2>Activity history</h2>
    <form class="comment-form" method="post" action="/admin/leads/<?= (int) $lead['id'] ?>/note">
      <?= csrf_field() ?>
      <label class="sr-only" for="note">Add a note</label>
      <input id="note" name="body" required maxlength="2000" placeholder="Log a call, email or note…">
      <button class="btn btn--ghost btn--sm" type="submit"><?= icon('plus') ?> Add note</button>
    </form>
    <ol class="timeline">
      <?php foreach ($activities as $a): ?>
        <li class="timeline__item">
          <p><?= nl2br(e($a['body'])) ?></p>
          <span class="muted small"><?= e($a['user_name'] ?? 'System') ?> · <?= fmt_date($a['created_at'], 'j M Y, H:i') ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
</div>

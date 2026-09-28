<?php
$heading = 'Settings';
$sub = 'Company details, brand, team and website operations.';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
$s = fn (string $key) => $settings[$key] ?? '';
$isAdmin = can('admin');
$roleLabels = ['admin' => 'Admin', 'team' => 'Team member', 'client' => 'Client'];
?>
<nav class="tabs tabs--links settings-nav" aria-label="Settings sections">
  <?php if ($isAdmin): ?><a href="#company">Company & brand</a><a href="#team">Team & roles</a><?php endif; ?>
  <?php if (can('admin', 'team')): ?><a href="#orders">Store orders</a><a href="#events">Event registrations</a><?php endif; ?>
  <a href="#account">My account</a>
</nav>

<?php if ($isAdmin): ?>
<form class="panel form" id="company" method="post" action="/admin/settings/company" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <h2>Company details</h2>
  <div class="form-grid">
    <label class="field"><span>Company name</span><input name="company_name" required value="<?= e($s('company_name')) ?>"></label>
    <label class="field"><span>Tagline</span><input name="company_tagline" value="<?= e($s('company_tagline')) ?>"></label>
    <label class="field"><span>Email</span><input type="email" name="company_email" value="<?= e($s('company_email')) ?>"></label>
    <label class="field"><span>Phone</span><input name="company_phone" value="<?= e($s('company_phone')) ?>"></label>
    <label class="field field--full"><span>Address</span><input name="company_address" value="<?= e($s('company_address')) ?>"></label>
  </div>

  <h2>Brand</h2>
  <div class="form-grid">
    <?php foreach (['color_navy' => 'Navy', 'color_red' => 'Red', 'color_gold' => 'Gold'] as $key => $label): ?>
      <label class="field color-field"><span><?= $label ?></span>
        <span class="color-field__row"><input type="color" value="<?= e($s($key)) ?>" data-color-sync="<?= $key ?>" aria-label="<?= $label ?> colour picker"><input name="<?= $key ?>" id="<?= $key ?>" value="<?= e($s($key)) ?>" pattern="#[0-9A-Fa-f]{6}" required></span>
      </label>
    <?php endforeach; ?>
    <div class="field">
      <span>Logo</span>
      <span class="logo-field"><img src="<?= e(media_url($s('logo_path'))) ?>" alt="Current logo" width="64" height="64"><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></span>
    </div>
  </div>

  <h2>Social links</h2>
  <div class="form-grid">
    <?php foreach (['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'] as $key => $label): ?>
      <label class="field"><span><?= $label ?></span><input type="url" name="social_<?= $key ?>" value="<?= e($s('social_' . $key)) ?>" placeholder="https://"></label>
    <?php endforeach; ?>
  </div>

  <h2>Notification preferences</h2>
  <div class="check-list">
    <label class="check"><input type="checkbox" name="notify_new_lead" value="1" <?= $s('notify_new_lead') === '1' ? 'checked' : '' ?>> New website enquiries</label>
    <label class="check"><input type="checkbox" name="notify_approval" value="1" <?= $s('notify_approval') === '1' ? 'checked' : '' ?>> Approval decisions and comments</label>
    <label class="check"><input type="checkbox" name="notify_order" value="1" <?= $s('notify_order') === '1' ? 'checked' : '' ?>> Store orders and event registrations</label>
  </div>
  <p class="muted small">Email delivery is not configured in the MVP — preferences are saved for when it is.</p>
  <div class="form-actions"><span class="spacer"></span><button class="btn btn--primary" type="submit">Save settings</button></div>
</form>

<section class="panel" id="team">
  <h2>Team members & roles</h2>
  <p class="muted small"><strong>Admin</strong>: everything, including settings and users. <strong>Team member</strong>: content, projects, leads, assets and analytics. <strong>Client</strong>: sees and approves content for their own projects only.</p>
  <div class="table-scroll">
    <table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th><span class="sr-only">Save</span></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><strong><?= e($u['name']) ?></strong><span class="muted small block"><?= e($u['title'] ?? '') ?><?= $u['client_name'] ? ' · ' . e($u['client_name']) : '' ?></span></td>
            <td class="muted"><?= e($u['email']) ?></td>
            <td colspan="3">
              <form class="inline-form" method="post" action="/admin/settings/users/<?= (int) $u['id'] ?>">
                <?= csrf_field() ?>
                <select name="role" aria-label="Role for <?= e($u['name']) ?>"><?php foreach ($roleLabels as $k => $l): ?><option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                <label class="check"><input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?>> Active</label>
                <button class="btn btn--ghost btn--sm" type="submit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <details class="add-user">
    <summary class="btn btn--ghost btn--sm"><?= icon('plus') ?> Add team member</summary>
    <form class="form" method="post" action="/admin/settings/users">
      <?= csrf_field() ?>
      <div class="form-grid">
        <label class="field"><span>Name</span><input name="name" required></label>
        <label class="field"><span>Email</span><input type="email" name="email" required></label>
        <label class="field"><span>Job title</span><input name="title"></label>
        <label class="field"><span>Role</span><select name="role"><?php foreach ($roleLabels as $k => $l): ?><option value="<?= $k ?>" <?= $k === 'team' ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Client (for client users)</span><select name="client_id"><option value="">—</option><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Temporary password (10+ characters)</span><input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
      </div>
      <button class="btn btn--primary" type="submit">Add user</button>
    </form>
  </details>
</section>
<?php endif; ?>

<?php if (can('admin', 'team')): ?>
<section class="panel" id="orders">
  <h2>Store orders</h2>
  <p class="muted small">Payment integration is coming soon — mark orders as paid once payment is received.</p>
  <div class="table-scroll">
    <table class="table">
      <thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Total</th><th>Placed</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= e($o['reference']) ?></strong></td>
            <td><?= e($o['customer_name']) ?><span class="muted small block"><?= e($o['email']) ?> · <?= e($o['city']) ?></span></td>
            <td><?= (int) $o['items'] ?></td>
            <td><?= money((int) $o['subtotal_cents']) ?></td>
            <td class="muted"><?= fmt_date($o['created_at'], 'j M, H:i') ?></td>
            <td>
              <form class="inline-form" method="post" action="/admin/orders/<?= (int) $o['id'] ?>">
                <?= csrf_field() ?>
                <select name="status" data-autosubmit aria-label="Status for order <?= e($o['reference']) ?>">
                  <?php foreach (['pending_payment' => 'Pending payment', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'] as $k => $l): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="6" class="muted">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="panel" id="events">
  <h2>Event registrations</h2>
  <div class="table-scroll">
    <table class="table">
      <thead><tr><th>Event</th><th>Date</th><th>Registrations</th><th>Capacity</th></tr></thead>
      <tbody>
        <?php foreach ($registrations as $r): ?>
          <tr><td><?= e($r['title']) ?></td><td><?= fmt_date($r['starts_at']) ?></td><td><?= (int) $r['registrations'] ?> (<?= (int) $r['booked'] ?> people)</td><td><?= (int) $r['booked'] ?>/<?= (int) $r['capacity'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<form class="panel form" id="account" method="post" action="/admin/settings/password">
  <?= csrf_field() ?>
  <h2>My account</h2>
  <p class="muted"><?= e(user()['name']) ?> · <?= e(user()['email']) ?> · <?= e($roleLabels[user()['role']]) ?></p>
  <div class="form-grid">
    <label class="field"><span>Current password</span><input type="password" name="current_password" required autocomplete="current-password"></label>
    <label class="field"><span>New password (10+ characters)</span><input type="password" name="new_password" minlength="10" required autocomplete="new-password"></label>
  </div>
  <button class="btn btn--navy" type="submit">Change password</button>
</form>

<?php if ($log): ?>
<section class="panel">
  <h2>Recent activity</h2>
  <ul class="simple-list">
    <?php foreach ($log as $l): ?><li><span><?= e($l['description']) ?></span><span class="muted small"><?= e($l['name'] ?? 'Website') ?> · <?= time_ago($l['created_at']) ?></span></li><?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

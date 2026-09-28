<?php
use App\Services\Catalog;

$heading = 'New lead';
$sub = 'Capture an enquiry that came in by phone, email or referral.';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<form class="panel form" method="post" action="/admin/leads">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="field"><span>Name <em>*</em></span><input name="name" required maxlength="120" value="<?= e(old('name')) ?>"></label>
    <label class="field"><span>Email <em>*</em></span><input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
    <label class="field"><span>Phone</span><input name="phone" maxlength="40" value="<?= e(old('phone')) ?>"></label>
    <label class="field"><span>Business or organisation</span><input name="organisation" maxlength="160" value="<?= e(old('organisation')) ?>"></label>
    <label class="field"><span>Service interest <em>*</em></span>
      <select name="service" required>
        <option value="">Choose…</option>
        <?php foreach (Catalog::SERVICES as $slug => $s): ?><option value="<?= e($slug) ?>" <?= old('service') === $slug ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>Budget</span>
      <select name="budget"><option value="">Choose…</option><?php foreach (Catalog::BUDGETS as $b): ?><option <?= old('budget') === $b ? 'selected' : '' ?>><?= e($b) ?></option><?php endforeach; ?></select>
    </label>
    <label class="field"><span>Source</span>
      <select name="source"><?php foreach (['manual' => 'Manual', 'referral' => 'Referral', 'phone' => 'Phone', 'email' => 'Email', 'social' => 'Social media'] as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select>
    </label>
    <label class="field"><span>Preferred start</span><input type="date" name="preferred_start" value="<?= e(old('preferred_start')) ?>"></label>
    <label class="field field--full"><span>Description <em>*</em></span><textarea name="details" rows="4" required><?= e(old('details')) ?></textarea></label>
  </div>
  <div class="form-actions"><a class="btn btn--text" href="/admin/leads">Cancel</a><span class="spacer"></span><button class="btn btn--primary" type="submit">Create lead</button></div>
</form>

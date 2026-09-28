<?php
use App\Services\Catalog;

$service = old('service', $selected);
$eyebrow = 'Contact'; $heading = 'Start a project'; $intro = 'Tell us what you’re planning. We reply to every enquiry within two working days.';
require BASE_PATH . '/app/Views/partials/page-hero.php';
?>
<section class="section container section--tight split split--top contact">
  <form class="card form" method="post" action="/contact" id="enquiry" novalidate>
    <?= csrf_field() ?>
    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="form-grid">
      <label class="field"><span>Name <em>*</em></span><input name="name" required maxlength="120" autocomplete="name" value="<?= e(old('name')) ?>"></label>
      <label class="field"><span>Email <em>*</em></span><input type="email" name="email" required autocomplete="email" value="<?= e(old('email')) ?>"></label>
      <label class="field"><span>Phone</span><input type="tel" name="phone" maxlength="40" autocomplete="tel" value="<?= e(old('phone')) ?>"></label>
      <label class="field"><span>Business or organisation</span><input name="organisation" maxlength="160" autocomplete="organization" value="<?= e(old('organisation')) ?>"></label>
      <label class="field"><span>Service required <em>*</em></span>
        <select name="service" required>
          <option value="">Choose a service…</option>
          <?php foreach (Catalog::SERVICES as $slug => $s): ?>
            <option value="<?= e($slug) ?>" <?= $service === $slug ? 'selected' : '' ?>><?= e($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>Budget range</span>
        <select name="budget">
          <option value="">Choose a range…</option>
          <?php foreach (Catalog::BUDGETS as $b): ?><option <?= old('budget') === $b ? 'selected' : '' ?>><?= e($b) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="field field--full"><span>Project details <em>*</em></span><textarea name="details" rows="6" required maxlength="5000" placeholder="What are you making, who is it for, and when do you need it?"><?= e(old('details')) ?></textarea></label>
      <label class="field"><span>Preferred start date</span><input type="date" name="preferred_start" value="<?= e(old('preferred_start')) ?>"></label>
    </div>
    <button class="btn btn--primary" type="submit">Send enquiry <?= icon('send') ?></button>
  </form>
  <aside class="contact__aside">
    <img class="split__img" src="<?= asset('assets/media/hero-camera.jpg') ?>" alt="" loading="lazy">
    <ul class="contact-list">
      <li><?= icon('mail') ?> <?= e(setting('company_email')) ?></li>
      <li><?= icon('phone') ?> <?= e(setting('company_phone')) ?></li>
      <li><?= icon('map-pin') ?> <?= e(setting('company_address')) ?></li>
    </ul>
    <?php require BASE_PATH . '/app/Views/partials/socials.php'; ?>
  </aside>
</section>

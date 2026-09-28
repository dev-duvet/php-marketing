<?php
$left = max(0, (int) $event['capacity'] - (int) $event['booked']);
$isPast = strtotime($event['starts_at']) < time();
?>
<section class="page-hero container">
  <a class="link-back" href="/events"><?= icon('chevron-left') ?> All events</a>
  <p class="eyebrow"><?= $isPast ? 'Past event' : 'Event' ?></p>
  <h1><?= e($event['title']) ?></h1>
  <p class="meta-row">
    <span><?= icon('calendar') ?> <?= fmt_date($event['starts_at'], 'l, j F Y · H:i') ?></span>
    <span><?= icon('map-pin') ?> <?= e($event['location']) ?></span>
  </p>
</section>
<section class="section container section--tight split split--top">
  <div>
    <img class="case-cover" src="<?= e(media_url($event['image'])) ?>" alt="">
    <div class="prose"><?= nl2br(e($event['description'])) ?></div>
  </div>
  <aside class="card" id="register">
    <h2>Register</h2>
    <p class="muted"><?= (int) $event['capacity'] ?> spaces · <strong><?= $left ?> left</strong></p>
    <?php if ($isPast): ?>
      <p class="notice">Registration has closed for this event.</p>
    <?php elseif ($left === 0): ?>
      <p class="notice">This event is fully booked.</p>
    <?php else: ?>
      <form class="form" method="post" action="/events/<?= e($event['slug']) ?>/register">
        <?= csrf_field() ?>
        <label class="field"><span>Name <em>*</em></span><input name="name" required autocomplete="name" value="<?= e(old('name')) ?>"></label>
        <label class="field"><span>Email <em>*</em></span><input type="email" name="email" required autocomplete="email" value="<?= e(old('email')) ?>"></label>
        <label class="field"><span>Phone</span><input type="tel" name="phone" autocomplete="tel" value="<?= e(old('phone')) ?>"></label>
        <label class="field"><span>Number of people</span>
          <select name="guests"><?php for ($i = 1; $i <= min(5, $left); $i++): ?><option><?= $i ?></option><?php endfor; ?></select>
        </label>
        <button class="btn btn--primary" type="submit">Reserve my spot</button>
      </form>
    <?php endif; ?>
  </aside>
</section>

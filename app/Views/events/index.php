<?php $eyebrow = 'Events'; $heading = 'Upcoming events'; $intro = 'Creative meetups, shoots, workshops and community events.';
require BASE_PATH . '/app/Views/partials/page-hero.php'; ?>
<section class="section container section--tight">
  <div class="grid grid--3">
    <?php foreach ($upcoming as $event): ?>
      <?php require BASE_PATH . '/app/Views/partials/event-card.php'; ?>
    <?php endforeach; ?>
  </div>
  <?php if (!$upcoming): ?><p class="muted">No upcoming events right now — check back soon.</p><?php endif; ?>
</section>
<?php if ($past): ?>
  <section class="section container">
    <h2>Event archive</h2>
    <ul class="archive-list">
      <?php foreach ($past as $event): ?>
        <li><a href="/events/<?= e($event['slug']) ?>"><strong><?= e($event['title']) ?></strong></a><span class="muted"><?= fmt_date($event['starts_at']) ?> · <?= e($event['location']) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php $eyebrow = 'Services'; $heading = 'What we do'; $intro = 'End-to-end creative media — from the first idea to the final post.';
require BASE_PATH . '/app/Views/partials/page-hero.php'; ?>

<section class="section container service-list">
  <?php foreach ($services as $slug => $service): ?>
    <article class="service-row" id="<?= e($slug) ?>">
      <img src="<?= asset($service['image']) ?>" alt="" loading="lazy">
      <div>
        <h2><?= e($service['name']) ?></h2>
        <p class="lead"><?= e($service['summary']) ?></p>
        <p class="muted"><?= e($service['detail']) ?></p>
        <a class="btn btn--navy" href="/contact?service=<?= e($slug) ?>#enquiry">Enquire about <?= e(strtolower($service['name'])) ?> <?= icon('arrow-right') ?></a>
      </div>
    </article>
  <?php endforeach; ?>
</section>

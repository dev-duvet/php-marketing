<?php $eyebrow = 'Store'; $heading = 'Creative goods'; $intro = 'Branded essentials for everyday creatives.';
require BASE_PATH . '/app/Views/partials/page-hero.php'; ?>
<section class="section container section--tight">
  <p class="notice"><?= icon('clock') ?> Online payment integration is coming soon. Orders are confirmed by email with payment details.</p>
  <div class="grid grid--4">
    <?php foreach ($products as $product): ?>
      <?php require BASE_PATH . '/app/Views/partials/product-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>

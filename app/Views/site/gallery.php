<?php
use App\Services\Catalog;

$eyebrow = 'Gallery'; $heading = 'Social content'; $intro = 'Photography, video and creative content from recent work and behind the scenes.';
require BASE_PATH . '/app/Views/partials/page-hero.php';
?>
<section class="container section section--tight">
  <nav class="filters" aria-label="Filter gallery" data-gallery-filters>
    <a href="/gallery" class="filter <?= $category === '' ? 'is-active' : '' ?>" data-filter="">All</a>
    <?php foreach (Catalog::GALLERY_CATEGORIES as $key => $label): ?>
      <a href="/gallery?category=<?= e($key) ?>" class="filter <?= $category === $key ? 'is-active' : '' ?>" data-filter="<?= e($key) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="gallery-grid" data-gallery>
    <?php $shown = 0; foreach ($items as $item): $hidden = $category !== '' && $item['public_category'] !== $category; $shown += $hidden ? 0 : 1; ?>
      <?php require BASE_PATH . '/app/Views/partials/media-card.php'; ?>
    <?php endforeach; ?>
  </div>
  <p class="muted center" data-gallery-empty <?= $shown ? 'hidden' : '' ?>>Nothing in this category yet.</p>
</section>

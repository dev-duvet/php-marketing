<?php
/** @var array $item asset row with public_category */
$categories = App\Services\Catalog::GALLERY_CATEGORIES;
$isVideo = ($item['type'] ?? '') === 'video' || ($item['public_category'] ?? '') === 'video';
?>
<figure class="media-card" data-category="<?= e($item['public_category'] ?? '') ?>" <?= !empty($hidden) ? 'hidden' : '' ?>>
  <?php if (($item['type'] ?? 'image') === 'video'): ?>
    <video src="<?= e(media_url($item['path'])) ?>" muted playsinline preload="metadata"></video>
  <?php else: ?>
    <img src="<?= e(media_url($item['path'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
  <?php endif; ?>
  <?php if ($isVideo): ?><span class="media-card__play" aria-hidden="true"><?= icon('play') ?></span><?php endif; ?>
  <figcaption>
    <span class="chip chip--light"><?= e($categories[$item['public_category']] ?? 'Media') ?></span>
    <span class="media-card__title"><?= e($item['title']) ?></span>
  </figcaption>
</figure>

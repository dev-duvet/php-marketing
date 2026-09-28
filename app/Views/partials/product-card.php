<a class="product-card" href="/store/<?= e($product['slug']) ?>">
  <span class="product-card__img"><img src="<?= e(media_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy"></span>
  <strong><?= e($product['name']) ?></strong>
  <span class="muted"><?= money((int) $product['price_cents']) ?></span>
</a>

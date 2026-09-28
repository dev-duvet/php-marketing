<section class="section container split split--top product">
  <div class="product__img"><img src="<?= e(media_url($product['image'])) ?>" alt="<?= e($product['name']) ?>"></div>
  <div>
    <a class="link-back" href="/store"><?= icon('chevron-left') ?> All products</a>
    <h1><?= e($product['name']) ?></h1>
    <p class="price"><?= money((int) $product['price_cents']) ?></p>
    <p class="lead muted"><?= e($product['description']) ?></p>
    <?php if ((int) $product['stock'] > 0): ?>
      <form method="post" action="/store/cart/add" class="add-to-cart">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <div class="qty" data-qty>
          <button type="button" class="qty__btn" data-step="-1" aria-label="Decrease quantity">−</button>
          <label class="sr-only" for="qty">Quantity</label>
          <input id="qty" type="number" name="quantity" value="1" min="1" max="<?= min(10, (int) $product['stock']) ?>" inputmode="numeric">
          <button type="button" class="qty__btn" data-step="1" aria-label="Increase quantity">+</button>
        </div>
        <button class="btn btn--primary" type="submit"><?= icon('cart') ?> Add to cart</button>
      </form>
      <p class="muted small"><?= (int) $product['stock'] ?> in stock</p>
    <?php else: ?>
      <p class="notice">Sold out — check back soon.</p>
    <?php endif; ?>
  </div>
</section>

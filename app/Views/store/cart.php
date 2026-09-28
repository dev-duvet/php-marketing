<section class="section container narrow">
  <h1>Your cart</h1>
  <?php if (!$cart['items']): ?>
    <p class="lead muted">Your cart is empty.</p>
    <a class="btn btn--navy" href="/store">Browse the store</a>
  <?php else: ?>
    <form method="post" action="/store/cart/update" class="card">
      <?= csrf_field() ?>
      <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Update cart</button>
      <ul class="cart-list">
        <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
          <li class="cart-row">
            <img src="<?= e(media_url($p['image'])) ?>" alt="" width="72" height="72">
            <div class="cart-row__name"><a href="/store/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a><span class="muted small"><?= money((int) $p['price_cents']) ?> each</span></div>
            <div class="qty" data-qty>
              <button type="button" class="qty__btn" data-step="-1" aria-label="Decrease quantity of <?= e($p['name']) ?>">−</button>
              <input type="number" name="quantities[<?= (int) $p['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0" max="10" aria-label="Quantity of <?= e($p['name']) ?>">
              <button type="button" class="qty__btn" data-step="1" aria-label="Increase quantity of <?= e($p['name']) ?>">+</button>
            </div>
            <strong class="cart-row__total"><?= money($item['line']) ?></strong>
            <button class="icon-btn" type="submit" name="remove" value="<?= (int) $p['id'] ?>" aria-label="Remove <?= e($p['name']) ?>"><?= icon('trash') ?></button>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="cart-summary">
        <button class="btn btn--ghost" type="submit">Update cart</button>
        <span>Subtotal <strong><?= money($cart['subtotal']) ?></strong></span>
      </div>
    </form>
    <div class="button-row button-row--end">
      <a class="btn btn--text" href="/store">Continue shopping</a>
      <a class="btn btn--primary" href="/store/checkout">Checkout <?= icon('arrow-right') ?></a>
    </div>
  <?php endif; ?>
</section>

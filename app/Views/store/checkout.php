<section class="section container split split--top">
  <form class="card form" method="post" action="/store/checkout">
    <?= csrf_field() ?>
    <h1>Checkout</h1>
    <div class="form-grid">
      <label class="field"><span>Full name <em>*</em></span><input name="customer_name" required autocomplete="name" value="<?= e(old('customer_name')) ?>"></label>
      <label class="field"><span>Email <em>*</em></span><input type="email" name="email" required autocomplete="email" value="<?= e(old('email')) ?>"></label>
      <label class="field"><span>Phone</span><input type="tel" name="phone" autocomplete="tel" value="<?= e(old('phone')) ?>"></label>
      <label class="field field--full"><span>Delivery address <em>*</em></span><input name="address" required autocomplete="street-address" value="<?= e(old('address')) ?>"></label>
      <label class="field"><span>City <em>*</em></span><input name="city" required autocomplete="address-level2" value="<?= e(old('city')) ?>"></label>
      <label class="field"><span>Postal code <em>*</em></span><input name="postal_code" required autocomplete="postal-code" value="<?= e(old('postal_code')) ?>"></label>
    </div>
    <div class="notice"><?= icon('clock') ?> <span><strong>Payment integration coming soon.</strong> Your order is saved and we’ll email payment details to confirm it.</span></div>
    <button class="btn btn--primary" type="submit">Place order</button>
  </form>
  <aside class="card">
    <h2>Order summary</h2>
    <ul class="summary-list">
      <?php foreach ($cart['items'] as $item): ?>
        <li><span><?= e($item['product']['name']) ?> × <?= (int) $item['quantity'] ?></span><span><?= money($item['line']) ?></span></li>
      <?php endforeach; ?>
      <li class="summary-list__total"><span>Subtotal</span><strong><?= money($cart['subtotal']) ?></strong></li>
    </ul>
    <a class="link-arrow" href="/store/cart">Edit cart</a>
  </aside>
</section>

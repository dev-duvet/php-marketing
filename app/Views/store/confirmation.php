<section class="section container narrow">
  <div class="card center">
    <span class="success-mark"><?= icon('check') ?></span>
    <h1>Thank you, <?= e(explode(' ', $order['customer_name'])[0]) ?>!</h1>
    <p class="lead">Your order <strong><?= e($order['reference']) ?></strong> has been received.</p>
    <p class="muted">Payment integration is coming soon — we’ll email <?= e($order['email']) ?> with payment details to confirm your order.</p>
    <ul class="summary-list">
      <?php foreach ($items as $item): ?>
        <li><span><?= e($item['product_name']) ?> × <?= (int) $item['quantity'] ?></span><span><?= money((int) $item['line_total_cents']) ?></span></li>
      <?php endforeach; ?>
      <li class="summary-list__total"><span>Total</span><strong><?= money((int) $order['subtotal_cents']) ?></strong></li>
    </ul>
    <a class="btn btn--navy" href="/store">Back to the store</a>
  </div>
</section>

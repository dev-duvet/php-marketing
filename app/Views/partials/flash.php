<?php foreach (take_flashes() as $flash): ?>
  <div class="alert alert--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
    <?= icon($flash['type'] === 'error' ? 'close' : 'check') ?>
    <span><?= e($flash['message']) ?></span>
  </div>
<?php endforeach; ?>

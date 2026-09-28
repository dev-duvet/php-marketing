<div class="section-head">
  <div>
    <p class="eyebrow"><?= e($eyebrow) ?></p>
    <h2><?= e($heading) ?></h2>
  </div>
  <?php if (!empty($intro)): ?><p class="section-head__intro muted"><?= e($intro) ?></p><?php endif; ?>
  <?php if (!empty($link)): ?><a class="link-arrow" href="<?= e($link[0]) ?>"><?= e($link[1]) ?> <?= icon('arrow-right') ?></a><?php endif; ?>
</div>

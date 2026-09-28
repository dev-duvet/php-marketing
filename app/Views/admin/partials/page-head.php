<div class="page-head">
  <div>
    <h1><?= e($heading) ?></h1>
    <?php if (!empty($sub)): ?><p class="muted"><?= e($sub) ?></p><?php endif; ?>
  </div>
  <?php if (!empty($actions)): ?><div class="page-head__actions"><?= $actions ?></div><?php endif; ?>
</div>

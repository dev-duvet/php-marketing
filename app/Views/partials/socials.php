<?php
$networks = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'];
?>
<ul class="socials">
  <?php foreach ($networks as $key => $label): ?>
    <?php if ($href = setting('social_' . $key)): ?>
      <li><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer" aria-label="CreateZA on <?= e($label) ?>"><?= icon($key) ?></a></li>
    <?php endif; ?>
  <?php endforeach; ?>
</ul>

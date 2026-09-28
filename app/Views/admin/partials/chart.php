<?php
/** @var array $chart ['periods' => [...], 'series' => [platform => [values]]] */
use App\Services\Catalog;

$w = 640; $h = 220; $padL = 48; $padR = 12; $padT = 12; $padB = 28;
$periods = $chart['periods'];
$max = max(1, ...array_map(fn ($s) => $s ? max($s) : 0, array_values($chart['series']) ?: [[0]]));
$max = (int) (ceil($max / 1000) * 1000);
$n = max(1, count($periods) - 1);
$x = fn (int $i) => $padL + ($w - $padL - $padR) * $i / $n;
$y = fn (int $v) => $padT + ($h - $padT - $padB) * (1 - $v / $max);
?>
<?php if (!$periods): ?>
  <p class="muted">No analytics yet. Add monthly figures on the Analytics page.</p>
<?php else: ?>
<figure class="chart">
  <svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="Monthly reach by platform (demo figures)">
    <?php for ($g = 0; $g <= 4; $g++): $gv = (int) ($max * $g / 4); ?>
      <line class="chart__grid" x1="<?= $padL ?>" x2="<?= $w - $padR ?>" y1="<?= $y($gv) ?>" y2="<?= $y($gv) ?>"/>
      <text class="chart__label" x="<?= $padL - 8 ?>" y="<?= $y($gv) + 4 ?>" text-anchor="end"><?= $gv >= 1000 ? round($gv / 1000, 1) . 'k' : $gv ?></text>
    <?php endfor; ?>
    <?php foreach ($periods as $i => $p): ?>
      <text class="chart__label" x="<?= $x($i) ?>" y="<?= $h - 8 ?>" text-anchor="middle"><?= date('M', strtotime($p . '-01')) ?></text>
    <?php endforeach; ?>
    <?php foreach ($chart['series'] as $platform => $values): ?>
      <?php $points = implode(' ', array_map(fn ($v, $i) => round($x($i), 1) . ',' . round($y($v), 1), $values, array_keys($values))); ?>
      <polyline class="chart__line chart__line--<?= e($platform) ?>" points="<?= $points ?>"/>
      <?php foreach ($values as $i => $v): ?>
        <circle class="chart__dot chart__line--<?= e($platform) ?>" cx="<?= round($x($i), 1) ?>" cy="<?= round($y($v), 1) ?>" r="3"><title><?= e(Catalog::PLATFORMS[$platform]) ?> · <?= date('M Y', strtotime($periods[$i] . '-01')) ?>: <?= number_format($v) ?></title></circle>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </svg>
  <figcaption class="legend">
    <?php foreach (Catalog::PLATFORMS as $key => $label): ?>
      <span><i class="legend__swatch chart__line--<?= $key ?>"></i><?= $label ?></span>
    <?php endforeach; ?>
    <span class="chip chip--muted">Demo data</span>
  </figcaption>
</figure>
<?php endif; ?>

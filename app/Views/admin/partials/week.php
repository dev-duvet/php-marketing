<?php
/** @var string $weekStart @var array $week grid[platform][date] => items */
use App\Services\Catalog;

$days = array_map(fn ($i) => date('Y-m-d', strtotime($weekStart . " +{$i} days")), range(0, 6));
$today = date('Y-m-d');
$linkable = can('admin', 'team');
?>
<div class="table-scroll">
  <table class="week">
    <thead>
      <tr>
        <th scope="col"><span class="sr-only">Platform</span></th>
        <?php foreach ($days as $day): ?>
          <th scope="col" class="<?= $day === $today ? 'is-today' : '' ?>"><?= date('D', strtotime($day)) ?><small><?= date('M j', strtotime($day)) ?></small></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach (Catalog::PLATFORMS as $key => $label): ?>
        <tr>
          <th scope="row"><span class="platform platform--<?= $key ?>"><?= icon($key) ?></span><?= $label ?></th>
          <?php foreach ($days as $day): ?>
            <td class="<?= $day === $today ? 'is-today' : '' ?>">
              <?php foreach ($week[$key][$day] ?? [] as $item): ?>
                <?php $href = $linkable ? "/admin/content/{$item['id']}/edit" : "/admin/approvals/{$item['id']}"; ?>
                <a class="slot slot--<?= e($item['status']) ?>" href="<?= $href ?>" title="<?= e($item['title'] . ' — ' . status_label($item['status'])) ?>">
                  <strong><?= e($item['format']) ?></strong><span><?= date('H:i', strtotime($item['publish_at'])) ?></span>
                </a>
              <?php endforeach; ?>
              <?php if (empty($week[$key][$day])): ?><span class="slot-empty" aria-hidden="true">—</span><?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

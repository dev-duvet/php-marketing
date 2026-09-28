<?php
use App\Services\Catalog;

$eyebrow = 'Work'; $heading = 'Selected work'; $intro = 'A closer look at recent creative projects across photo, video, web and social. Projects shown are demo placeholders until real case studies are published.';
require BASE_PATH . '/app/Views/partials/page-hero.php';
?>
<section class="section container section--tight">
  <div class="grid grid--2">
    <?php foreach ($projects as $project): ?>
      <article class="case-card">
        <a href="/work/<?= e($project['slug']) ?>"><img src="<?= e(media_url($project['cover_image'])) ?>" alt="" loading="lazy"></a>
        <div class="case-card__body">
          <span class="chip"><?= e(Catalog::serviceName($project['service_type'])) ?></span>
          <h2><a href="/work/<?= e($project['slug']) ?>"><?= e($project['name']) ?></a></h2>
          <p class="muted"><?= e($project['description']) ?></p>
          <a class="link-arrow" href="/work/<?= e($project['slug']) ?>">View case study <?= icon('arrow-right') ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

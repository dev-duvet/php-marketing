<?php use App\Services\Catalog; ?>
<section class="page-hero container">
  <a class="link-back" href="/work"><?= icon('chevron-left') ?> All work</a>
  <p class="eyebrow"><?= e(Catalog::serviceName($project['service_type'])) ?></p>
  <h1><?= e($project['name']) ?></h1>
</section>
<section class="container">
  <img class="case-cover" src="<?= e(media_url($project['cover_image'])) ?>" alt="">
</section>
<section class="section container split split--top">
  <div>
    <h2>Project overview</h2>
    <p class="lead"><?= nl2br(e($project['description'])) ?></p>
    <?php if ($project['deliverables']): ?>
      <h3>Deliverables</h3>
      <ul class="ticks"><?php foreach (array_filter(explode("\n", $project['deliverables'])) as $line): ?><li><?= e(trim($line)) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
  <aside class="panel">
    <h2>Project outcomes</h2>
    <?php if ($project['outcomes']): ?>
      <ul class="ticks"><?php foreach (array_filter(explode("\n", $project['outcomes'])) as $line): ?><li><?= e(trim($line)) ?></li><?php endforeach; ?></ul>
    <?php else: ?>
      <p class="muted">Outcomes will be added once the project wraps.</p>
    <?php endif; ?>
  </aside>
</section>
<?php if ($gallery): ?>
  <section class="section container section--tight">
    <h2>Gallery</h2>
    <div class="gallery-grid">
      <?php foreach ($gallery as $item): ?>
        <?php $item['public_category'] = $item['public_category'] ?: 'photography'; require BASE_PATH . '/app/Views/partials/media-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
<section class="section container">
  <div class="cta">
    <div><h2>Want something similar?</h2><p class="muted">Tell us about your project.</p></div>
    <a class="btn btn--primary" href="/contact?service=<?= e($project['service_type']) ?>#enquiry">Start a project <?= icon('arrow-right') ?></a>
  </div>
</section>

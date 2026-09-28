<?php
use App\Services\Catalog;

$featured = array_slice(Catalog::SERVICES, 0, 4, true);
?>
<section class="hero container">
  <div class="hero__copy">
    <h1 class="display">Creative work<br>made to move<span class="accent">.</span></h1>
    <p class="lead">Photography. Video. Websites. Social content.<br>Creative media for bold ideas and bigger stories.</p>
    <div class="button-row">
      <a class="btn btn--navy" href="/contact">Start a project <?= icon('arrow-right') ?></a>
      <a class="btn btn--text" href="/gallery?category=video"><span class="play-ring"><?= icon('play') ?></span> Watch showreel</a>
    </div>
  </div>
  <div class="hero__collage" aria-hidden="true">
    <span class="block block--red"></span>
    <span class="block block--navy"></span>
    <span class="block block--gold"></span>
    <img class="c1" src="<?= asset('assets/media/hero-camera.jpg') ?>" alt="">
    <img class="c2" src="<?= asset('assets/media/hero-session.jpg') ?>" alt="">
    <img class="c3" src="<?= asset('assets/media/websites.jpg') ?>" alt="">
    <img class="c4" src="<?= asset('assets/media/hero-street.jpg') ?>" alt="">
    <img class="c5" src="<?= asset('assets/media/hero-creator.jpg') ?>" alt="">
  </div>
</section>

<section class="container">
  <div class="ribbon">
    <strong>Follow the creative journey</strong>
    <?php require BASE_PATH . '/app/Views/partials/socials.php'; ?>
    <span class="ribbon__note muted small">Creative content. Real moments.<br>Across every platform.</span>
  </div>
</section>

<section class="section container">
  <?php $eyebrow = 'Services'; $heading = 'What we do'; $intro = 'End-to-end creative media services to help you tell your story, connect your audience and create meaningful impact.'; $link = ['/services', 'Explore all services'];
  require BASE_PATH . '/app/Views/partials/section-head.php'; ?>
  <div class="grid grid--4">
    <?php foreach ($featured as $slug => $service): ?>
      <article class="service-card">
        <img src="<?= asset($service['image']) ?>" alt="" loading="lazy">
        <div class="service-card__body">
          <h3><?= e($service['name']) ?></h3>
          <p class="muted"><?= e($service['summary']) ?></p>
          <a class="link-arrow" href="/services#<?= e($slug) ?>">Learn more <?= icon('arrow-right') ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section container">
  <?php $eyebrow = 'Gallery'; $heading = 'Social content'; $intro = 'A mix of photography, video and creative content from recent work and behind the scenes.'; $link = ['/gallery', 'View full gallery'];
  require BASE_PATH . '/app/Views/partials/section-head.php'; ?>
  <div class="mosaic">
    <?php foreach ($gallery as $item): ?>
      <?php require BASE_PATH . '/app/Views/partials/media-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>

<section class="section container">
  <?php $eyebrow = 'Work'; $heading = 'Selected work'; $intro = 'A closer look at some of our recent creative projects across photo, video, web and social.'; $link = ['/work', 'View all work'];
  require BASE_PATH . '/app/Views/partials/section-head.php'; ?>
  <div class="grid grid--3">
    <?php foreach ($work as $project): ?>
      <a class="work-card" href="/work/<?= e($project['slug']) ?>">
        <img src="<?= e(media_url($project['cover_image'])) ?>" alt="" loading="lazy">
        <span class="chip"><?= e(Catalog::serviceName($project['service_type'])) ?></span>
        <strong><?= e($project['name']) ?></strong>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section--navy">
  <div class="container split">
    <div>
      <p class="eyebrow eyebrow--light">Community</p>
      <h2>Made with creators, for creators.</h2>
      <p class="lead">CreateZA is creator-led. We run shoots, workshops and meetups that help new photographers, filmmakers and storytellers grow — and we bring that energy to every client project.</p>
      <div class="button-row">
        <a class="btn btn--primary" href="/events">See upcoming events</a>
        <a class="btn btn--outline-light" href="/about">Our story</a>
      </div>
    </div>
    <img class="split__img" src="<?= asset('assets/media/community.jpg') ?>" alt="A group of young creators smiling together" loading="lazy">
  </div>
</section>

<section class="section container">
  <?php $eyebrow = 'Store'; $heading = 'Creative goods'; $intro = 'Branded essentials for everyday creatives.'; $link = ['/store', 'Visit store'];
  require BASE_PATH . '/app/Views/partials/section-head.php'; ?>
  <div class="grid grid--4">
    <?php foreach ($products as $product): ?>
      <?php require BASE_PATH . '/app/Views/partials/product-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>

<section class="section container">
  <?php $eyebrow = 'Events'; $heading = 'Upcoming events'; $intro = 'Creative meetups, shoots, workshops and community events.'; $link = ['/events', 'View all events'];
  require BASE_PATH . '/app/Views/partials/section-head.php'; ?>
  <div class="grid grid--3">
    <?php foreach ($events as $event): ?>
      <?php require BASE_PATH . '/app/Views/partials/event-card.php'; ?>
    <?php endforeach; ?>
    <?php if (!$events): ?><p class="muted">New events are announced soon.</p><?php endif; ?>
  </div>
</section>

<section class="section container">
  <div class="cta">
    <div>
      <h2>Let’s create together.</h2>
      <p class="muted">Have a project in mind? Tell us about it and we’ll come back with ideas, timelines and a quote.</p>
    </div>
    <a class="btn btn--primary" href="/contact">Start a project <?= icon('arrow-right') ?></a>
  </div>
</section>

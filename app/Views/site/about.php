<?php $eyebrow = 'About'; $heading = 'A creator-led media company from South Africa.'; $intro = 'We make photography, video, websites and social content for brands, organisations and people with something to say.';
require BASE_PATH . '/app/Views/partials/page-hero.php'; ?>

<section class="section container split">
  <img class="split__img" src="<?= asset('assets/media/hero-studio.jpg') ?>" alt="A photographer directing a studio shoot" loading="lazy">
  <div>
    <h2>Our story</h2>
    <p>CreateZA started with a camera, a phone and a lot of late-night edits. What began as shooting for friends and local businesses has grown into a studio that plans, produces and publishes content across every major platform.</p>
    <p>We keep things simple: understand the story, capture it beautifully, and make sure it reaches the right people.</p>
  </div>
</section>

<section class="section container">
  <h2>What we value</h2>
  <div class="grid grid--3">
    <article class="value-card"><span class="value-card__bar"></span><h3>Craft</h3><p class="muted">Every frame, cut and pixel is considered. We would rather do fewer things well.</p></article>
    <article class="value-card"><span class="value-card__bar value-card__bar--navy"></span><h3>Community</h3><p class="muted">We grow with the creators around us — through workshops, meetups and paid opportunities.</p></article>
    <article class="value-card"><span class="value-card__bar value-card__bar--gold"></span><h3>Clarity</h3><p class="muted">Clear briefs, honest timelines and straightforward pricing. No surprises.</p></article>
  </div>
</section>

<section class="section section--navy">
  <div class="container split">
    <div>
      <p class="eyebrow eyebrow--light">Creator community</p>
      <h2>Room for new voices.</h2>
      <p class="lead">Our community events give emerging photographers, videographers and designers a place to learn, shoot and connect.</p>
      <a class="btn btn--primary" href="/events">Join an event</a>
    </div>
    <img class="split__img" src="<?= asset('assets/media/community.jpg') ?>" alt="A group of creators smiling together" loading="lazy">
  </div>
</section>

<section class="section container">
  <div class="cta">
    <div><h2>Have a story to tell?</h2><p class="muted">Let’s talk about your next project.</p></div>
    <a class="btn btn--primary" href="/contact">Start a project <?= icon('arrow-right') ?></a>
  </div>
</section>

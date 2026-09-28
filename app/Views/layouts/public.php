<?php
$nav = ['/' => 'Home', '/about' => 'About', '/services' => 'Services', '/gallery' => 'Gallery', '/work' => 'Work', '/store' => 'Store', '/events' => 'Events', '/contact' => 'Contact'];
$cartCount = array_sum($_SESSION['cart'] ?? []);
$logo = media_url(setting('logo_path', 'assets/brand/createza-logo.jpg'));
?>
<!doctype html>
<html lang="en-ZA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($title ?? '') !== '' ? $title . ' — CreateZA' : 'CreateZA') ?></title>
  <meta name="description" content="CreateZA — photography, video, websites and social content for bold ideas and bigger stories.">
  <link rel="icon" href="<?= asset('assets/brand/createza-logo.jpg') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <?php require BASE_PATH . '/app/Views/partials/brand-vars.php'; ?>
</head>
<body class="site">
<?php require BASE_PATH . '/app/Views/partials/icons.php'; ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="container site-header__inner">
    <a class="site-logo" href="/" aria-label="CreateZA home"><img src="<?= e($logo) ?>" alt="CreateZA" width="56" height="56"></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
      <?= icon('menu') ?><span class="sr-only">Menu</span>
    </button>
    <nav class="site-nav" id="site-nav" aria-label="Main">
      <ul>
        <?php foreach ($nav as $href => $label): ?>
          <li><a href="<?= $href ?>" class="<?= active($href, $href === '/') ?>" <?= active($href, $href === '/') ? 'aria-current="page"' : '' ?>><?= $label ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="site-nav__actions">
        <a class="cart-link" href="/store/cart" aria-label="Cart, <?= (int) $cartCount ?> items"><?= icon('cart') ?><?php if ($cartCount): ?><span class="badge"><?= (int) $cartCount ?></span><?php endif; ?></a>
        <a class="btn btn--primary btn--sm" href="/contact">Start a project <?= icon('arrow-right') ?></a>
      </div>
    </nav>
  </div>
</header>

<main id="main">
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="container flash-wrap"><?php require BASE_PATH . '/app/Views/partials/flash.php'; ?></div>
  <?php endif; ?>
  <?= $content ?>
</main>

<footer class="site-footer">
  <div class="container site-footer__grid">
    <div>
      <img class="site-footer__logo" src="<?= e($logo) ?>" alt="CreateZA" width="72" height="72" loading="lazy">
      <p class="muted"><?= e(setting('company_tagline', 'Creative work made to move.')) ?></p>
    </div>
    <nav aria-label="Footer">
      <h2 class="footer-title">Quick links</h2>
      <ul class="footer-links">
        <?php foreach ($nav as $href => $label): ?><li><a href="<?= $href ?>"><?= $label ?></a></li><?php endforeach; ?>
      </ul>
    </nav>
    <div>
      <h2 class="footer-title">Let’s create together</h2>
      <p class="muted">Have a project in mind? We’d love to hear from you.</p>
      <p><a class="btn btn--primary btn--sm" href="/contact">Start a project <?= icon('arrow-right') ?></a></p>
      <p class="muted small"><?= e(setting('company_email')) ?></p>
    </div>
    <div>
      <h2 class="footer-title">Follow</h2>
      <?php require BASE_PATH . '/app/Views/partials/socials.php'; ?>
    </div>
  </div>
  <div class="container site-footer__base">
    <span>&copy; <?= date('Y') ?> <?= e(setting('company_name', 'CreateZA')) ?>. All rights reserved.</span>
    <a href="/admin">Team sign in</a>
  </div>
</footer>
<script src="<?= asset('assets/js/site.js') ?>" defer></script>
</body>
</html>

<?php
$me = user();
$nav = [
    ['/admin', 'Overview', 'home', true, ['admin', 'team', 'client']],
    ['/admin/content', 'Content calendar', 'calendar', false, ['admin', 'team', 'client']],
    ['/admin/projects', 'Projects', 'folder', false, ['admin', 'team', 'client']],
    ['/admin/leads', 'Leads', 'users', false, ['admin', 'team']],
    ['/admin/approvals', 'Approvals', 'check-circle', false, ['admin', 'team', 'client']],
    ['/admin/assets', 'Asset library', 'image', false, ['admin', 'team', 'client']],
    ['/admin/analytics', 'Analytics', 'chart', false, ['admin', 'team']],
    ['/admin/settings', 'Settings', 'settings', false, ['admin', 'team', 'client']],
];
[$scopeSql, $scopeParams] = App\Services\Auth::clientScope();
$awaiting = $me ? (int) scalar("SELECT COUNT(*) FROM content_items c LEFT JOIN projects p ON p.id = c.project_id WHERE c.status = 'awaiting_approval'{$scopeSql}", $scopeParams) : 0;
$initials = $me ? strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(explode(' ', $me['name']), 0, 2)))) : '';
?>
<!doctype html>
<html lang="en-ZA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e(($title ?? 'Dashboard') . ' — CreateZA Studio') ?></title>
  <link rel="icon" href="<?= asset('assets/brand/createza-logo.jpg') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
  <?php require BASE_PATH . '/app/Views/partials/brand-vars.php'; ?>
</head>
<body class="studio">
<?php require BASE_PATH . '/app/Views/partials/icons.php'; ?>
<a class="skip-link" href="#main">Skip to content</a>

<aside class="sidebar" id="sidebar" aria-label="Dashboard">
  <a class="sidebar__brand" href="/admin">
    <img src="<?= e(media_url(setting('logo_path', 'assets/brand/createza-logo.jpg'))) ?>" alt="" width="48" height="48">
    <span>CreateZA Studio</span>
  </a>
  <nav>
    <ul class="sidebar__nav">
      <?php foreach ($nav as [$href, $label, $ico, $exact, $roles]): ?>
        <?php if ($me && in_array($me['role'], $roles, true)): ?>
          <li>
            <a href="<?= $href ?>" class="<?= active($href, $exact) ?>" <?= active($href, $exact) ? 'aria-current="page"' : '' ?>>
              <?= icon($ico) ?><span><?= $label ?></span>
              <?php if ($href === '/admin/approvals' && $awaiting): ?><span class="count"><?= $awaiting ?></span><?php endif; ?>
            </a>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </nav>
  <?php if ($me): ?>
    <div class="sidebar__user">
      <span class="avatar"><?= e($initials) ?></span>
      <span class="sidebar__user-meta"><strong><?= e($me['name']) ?></strong><small><?= e(ucfirst($me['role'] === 'team' ? 'Team member' : $me['role'])) ?></small></span>
      <form method="post" action="/admin/logout"><?= csrf_field() ?>
        <button class="icon-btn icon-btn--dark" type="submit" aria-label="Sign out" title="Sign out"><?= icon('logout') ?></button>
      </form>
    </div>
  <?php endif; ?>
</aside>
<div class="sidebar-scrim" data-sidebar-close></div>

<div class="studio-main">
  <header class="topbar">
    <button class="icon-btn topbar__menu" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Open menu"><?= icon('menu') ?></button>
    <form class="topbar__search" action="/admin/assets" method="get" role="search">
      <?= icon('search') ?>
      <label class="sr-only" for="global-search">Search assets</label>
      <input id="global-search" type="search" name="q" placeholder="Search assets…">
    </form>
    <div class="topbar__actions">
      <a class="icon-btn" href="/admin/approvals" aria-label="<?= $awaiting ?> items awaiting approval"><?= icon('bell') ?><?php if ($awaiting): ?><span class="dot"></span><?php endif; ?></a>
      <a class="btn btn--ghost btn--sm" href="/" target="_blank" rel="noopener">View site</a>
      <?php if (can('admin', 'team')): ?>
        <a class="btn btn--navy btn--sm" href="/admin/content/new"><?= icon('plus') ?> New content</a>
      <?php endif; ?>
    </div>
  </header>

  <main id="main" class="studio-content">
    <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
</body>
</html>

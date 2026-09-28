<!doctype html>
<html lang="en-ZA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e(($title ?? 'Sign in') . ' — CreateZA Studio') ?></title>
  <link rel="icon" href="<?= asset('assets/brand/createza-logo.jpg') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
  <?php require BASE_PATH . '/app/Views/partials/brand-vars.php'; ?>
</head>
<body class="auth">
<?php require BASE_PATH . '/app/Views/partials/icons.php'; ?>
<main id="main" class="auth__main">
  <?= $content ?>
</main>
</body>
</html>

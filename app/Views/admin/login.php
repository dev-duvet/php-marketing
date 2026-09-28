<div class="auth-card">
  <img class="auth-card__logo" src="<?= e(media_url(setting('logo_path', 'assets/brand/createza-logo.jpg'))) ?>" alt="CreateZA" width="88" height="88">
  <h1>CreateZA Studio</h1>
  <p class="muted">Sign in to manage content, projects and leads.</p>
  <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
  <form class="form" method="post" action="/admin/login">
    <?= csrf_field() ?>
    <label class="field"><span>Email</span><input type="email" name="email" required autocomplete="username" autofocus value="<?= e(old('email')) ?>"></label>
    <label class="field"><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn--primary btn--block" type="submit">Sign in</button>
  </form>
  <a class="link-back" href="/"><?= icon('chevron-left') ?> Back to website</a>
</div>

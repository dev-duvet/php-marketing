<?php
declare(strict_types=1);

use App\Services\Auth;

test('demo passwords are stored as hashes, never plain text', function () {
    $hash = scalar('SELECT password_hash FROM users WHERE email = ?', ['admin@createza.test']);
    assert_true($hash !== 'test-password-123' && password_verify('test-password-123', $hash));
});

test('a correct email and password signs the user in', function () {
    assert_same(null, Auth::attempt('admin@createza.test', 'test-password-123', '127.0.0.1'));
    assert_same('admin', Auth::user()['role']);
    assert_true(isset($_SESSION['user_id']));
});

test('email matching is case-insensitive', function () {
    assert_same(null, Auth::attempt('ADMIN@CreateZA.test', 'test-password-123', '127.0.0.1'));
});

test('a wrong password is rejected and recorded', function () {
    assert_true(Auth::attempt('admin@createza.test', 'wrong', '127.0.0.2') !== null);
    assert_same(null, Auth::user());
    assert_same(1, (int) scalar('SELECT COUNT(*) FROM login_attempts WHERE email = ?', ['admin@createza.test']));
});

test('logins are locked after repeated failures', function () {
    for ($i = 0; $i < Auth::MAX_ATTEMPTS; $i++) {
        Auth::attempt('team@createza.test', 'nope', '127.0.0.3');
    }
    $error = Auth::attempt('team@createza.test', 'test-password-123', '127.0.0.3');
    assert_true($error !== null && str_contains($error, 'Too many'), 'expected lockout message');
});

test('inactive users cannot sign in', function () {
    q('UPDATE users SET is_active = 0 WHERE email = ?', ['designer@createza.test']);
    assert_true(Auth::attempt('designer@createza.test', 'test-password-123', '127.0.0.4') !== null);
});

test('client users are scoped to their own client', function () {
    Auth::attempt('client@createza.test', 'test-password-123', '127.0.0.5');
    [$sql, $params] = Auth::clientScope();
    assert_true(str_contains($sql, 'client_id'));
    assert_same((int) Auth::user()['client_id'], $params[0]);
});

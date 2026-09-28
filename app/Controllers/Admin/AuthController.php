<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;

final class AuthController extends Controller
{
    public function form(): void
    {
        if (Auth::user()) {
            redirect('/admin');
        }
        $this->view('admin/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public function login(): void
    {
        $error = Auth::attempt(input('email'), (string) ($_POST['password'] ?? ''), client_ip());
        if ($error !== null) {
            $this->failed($error, '/admin/login');
        }
        log_activity('auth.login', 'Signed in');
        $intended = $_SESSION['intended'] ?? '/admin';
        unset($_SESSION['intended']);
        redirect(str_starts_with($intended, '/admin') ? $intended : '/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('/admin/login');
    }
}

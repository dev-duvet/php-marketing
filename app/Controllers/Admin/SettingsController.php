<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\Upload;
use App\Services\Validator;
use RuntimeException;

final class SettingsController extends Controller
{
    private const TEXT_KEYS = ['company_name', 'company_tagline', 'company_email', 'company_phone', 'company_address'];
    private const COLOR_KEYS = ['color_navy', 'color_red', 'color_gold'];
    private const SOCIAL_KEYS = ['social_instagram', 'social_tiktok', 'social_youtube', 'social_x', 'social_facebook', 'social_linkedin'];
    private const NOTIFY_KEYS = ['notify_new_lead', 'notify_approval', 'notify_order'];

    public function index(): void
    {
        $this->admin('settings', [
            'title' => 'Settings',
            'settings' => array_column(rows('SELECT key, value FROM settings'), 'value', 'key'),
            'users' => rows('SELECT u.*, c.name AS client_name FROM users u LEFT JOIN clients c ON c.id = u.client_id ORDER BY u.role, u.name'),
            'clients' => rows('SELECT id, name FROM clients ORDER BY name'),
            'orders' => can('admin', 'team') ? rows('SELECT o.*, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS items FROM orders o ORDER BY o.created_at DESC LIMIT 20') : [],
            'registrations' => can('admin', 'team') ? rows('SELECT e.title, e.starts_at, e.capacity, COALESCE(SUM(r.guests), 0) AS booked, COUNT(r.id) AS registrations FROM events e LEFT JOIN event_registrations r ON r.event_id = e.id GROUP BY e.id ORDER BY e.starts_at DESC') : [],
            'log' => can('admin') ? rows('SELECT l.*, u.name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 15') : [],
        ]);
    }

    public function company(): void
    {
        $v = (new Validator($_POST))->required('company_name', 'Company name')->email('company_email');
        foreach (self::COLOR_KEYS as $key) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', input($key))) {
                $this->failed('Brand colours must be hex values like #14284B.', '/admin/settings');
            }
        }
        foreach (self::SOCIAL_KEYS as $key) {
            if (input($key) !== '' && !preg_match('#^https?://#', input($key))) {
                $this->failed('Social links must start with http:// or https://', '/admin/settings');
            }
        }
        if ($v->fails()) {
            $this->failed($v->firstError(), '/admin/settings');
        }
        foreach ([...self::TEXT_KEYS, ...self::COLOR_KEYS, ...self::SOCIAL_KEYS] as $key) {
            set_setting($key, mb_substr(input($key), 0, 255));
        }
        foreach (self::NOTIFY_KEYS as $key) {
            set_setting($key, isset($_POST[$key]) ? '1' : '0');
        }
        $logo = Upload::files('logo');
        if ($logo) {
            try {
                $stored = Upload::store($logo[0], ['image']);
                set_setting('logo_path', $stored['path']);
            } catch (RuntimeException $e) {
                $this->failed($e->getMessage(), '/admin/settings');
            }
        }
        log_activity('settings.updated', 'Company settings updated');
        flash('success', 'Settings saved.');
        redirect('/admin/settings');
    }

    public function addUser(): void
    {
        $v = (new Validator($_POST))
            ->required('name', 'Name')->required('email', 'Email')->email('email')
            ->in('role', ['admin', 'team', 'client'], 'role');
        if ($v->fails()) {
            $this->failed($v->firstError(), '/admin/settings#team');
        }
        if (strlen((string) ($_POST['password'] ?? '')) < 10) {
            $this->failed('Passwords must be at least 10 characters.', '/admin/settings#team');
        }
        if (row('SELECT id FROM users WHERE LOWER(email) = ?', [mb_strtolower(input('email'))])) {
            $this->failed('A user with that email already exists.', '/admin/settings#team');
        }
        $role = input('role') ?: 'team';
        $clientId = $role === 'client' && input('client_id') !== '' ? (int) input('client_id') : null;
        if ($role === 'client' && $clientId === null) {
            $this->failed('Client users must be linked to a client.', '/admin/settings#team');
        }
        q('INSERT INTO users (name, email, password_hash, role, client_id, title) VALUES (?, ?, ?, ?, ?, ?)', [
            input('name'), strtolower(input('email')), password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $role, $clientId, input('title'),
        ]);
        log_activity('user.created', 'User ' . input('email') . ' added');
        flash('success', 'Team member added.');
        redirect('/admin/settings#team');
    }

    public function updateUser(string $id): void
    {
        $target = row('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$target) {
            $this->notFound();
        }
        $role = input('role');
        if (!in_array($role, ['admin', 'team', 'client'], true)) {
            $this->failed('Choose a valid role.', '/admin/settings#team');
        }
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ((int) $target['id'] === Auth::id() && ($role !== 'admin' || !$active)) {
            $this->failed('You cannot remove your own admin access.', '/admin/settings#team');
        }
        q('UPDATE users SET role = ?, is_active = ? WHERE id = ?', [$role, $active, $target['id']]);
        flash('success', 'User updated.');
        redirect('/admin/settings#team');
    }

    public function password(): void
    {
        $user = Auth::user();
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password_hash'])) {
            $this->failed('Your current password is incorrect.', '/admin/settings#account');
        }
        $new = (string) ($_POST['new_password'] ?? '');
        if (strlen($new) < 10) {
            $this->failed('New passwords must be at least 10 characters.', '/admin/settings#account');
        }
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password updated.');
        redirect('/admin/settings#account');
    }

    public function orderStatus(string $id): void
    {
        $status = input('status');
        if (!in_array($status, ['pending_payment', 'paid', 'fulfilled', 'cancelled'], true)) {
            $this->failed('Choose a valid order status.', '/admin/settings#orders');
        }
        q('UPDATE orders SET status = ? WHERE id = ?', [$status, (int) $id]);
        flash('success', 'Order updated.');
        redirect('/admin/settings#orders');
    }
}

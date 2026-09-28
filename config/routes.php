<?php
declare(strict_types=1);

use App\Controllers\Admin\ApprovalController;
use App\Controllers\Admin\AssetController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LeadController;
use App\Controllers\Admin\ProjectController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\EventController;
use App\Controllers\SiteController;
use App\Controllers\StoreController;

/** @var App\Services\Router $router */

$staff = ['admin', 'team'];
$everyone = ['admin', 'team', 'client'];

// Public website
$router->get('/', [SiteController::class, 'home']);
$router->get('/about', [SiteController::class, 'about']);
$router->get('/services', [SiteController::class, 'services']);
$router->get('/gallery', [SiteController::class, 'gallery']);
$router->get('/work', [SiteController::class, 'work']);
$router->get('/work/{slug}', [SiteController::class, 'caseStudy']);
$router->get('/contact', [SiteController::class, 'contact']);
$router->post('/contact', [SiteController::class, 'submitContact']);
$router->get('/health', [SiteController::class, 'health']);
$router->get('/uploads/{name}', [SiteController::class, 'upload']);

$router->get('/store', [StoreController::class, 'index']);
$router->get('/store/cart', [StoreController::class, 'cart']);
$router->post('/store/cart/add', [StoreController::class, 'add']);
$router->post('/store/cart/update', [StoreController::class, 'update']);
$router->get('/store/checkout', [StoreController::class, 'checkout']);
$router->post('/store/checkout', [StoreController::class, 'placeOrder']);
$router->get('/store/order/{reference}', [StoreController::class, 'confirmation']);
$router->get('/store/{slug}', [StoreController::class, 'show']);

$router->get('/events', [EventController::class, 'index']);
$router->get('/events/{slug}', [EventController::class, 'show']);
$router->post('/events/{slug}/register', [EventController::class, 'register']);

// Dashboard
$router->get('/admin/login', [AuthController::class, 'form']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout'], $everyone);

$router->get('/admin', [DashboardController::class, 'overview'], $everyone);
$router->get('/admin/analytics', [DashboardController::class, 'analytics'], $staff);
$router->post('/admin/analytics', [DashboardController::class, 'saveAnalytics'], $staff);

$router->get('/admin/content', [ContentController::class, 'calendar'], $everyone);
$router->get('/admin/content/new', [ContentController::class, 'create'], $staff);
$router->post('/admin/content', [ContentController::class, 'store'], $staff);
$router->get('/admin/content/{id}/edit', [ContentController::class, 'edit'], $staff);
$router->post('/admin/content/{id}', [ContentController::class, 'update'], $staff);
$router->post('/admin/content/{id}/delete', [ContentController::class, 'destroy'], $staff);

$router->get('/admin/approvals', [ApprovalController::class, 'index'], $everyone);
$router->get('/admin/approvals/{id}', [ApprovalController::class, 'show'], $everyone);
$router->post('/admin/approvals/{id}/comment', [ApprovalController::class, 'comment'], $everyone);
$router->post('/admin/approvals/{id}/decide', [ApprovalController::class, 'decide'], $everyone);

$router->get('/admin/projects', [ProjectController::class, 'index'], $everyone);
$router->get('/admin/projects/new', [ProjectController::class, 'create'], $staff);
$router->post('/admin/projects', [ProjectController::class, 'store'], $staff);
$router->get('/admin/projects/{id}', [ProjectController::class, 'show'], $everyone);
$router->get('/admin/projects/{id}/edit', [ProjectController::class, 'edit'], $staff);
$router->post('/admin/projects/{id}', [ProjectController::class, 'update'], $staff);

$router->get('/admin/leads', [LeadController::class, 'index'], $staff);
$router->get('/admin/leads/new', [LeadController::class, 'create'], $staff);
$router->post('/admin/leads', [LeadController::class, 'store'], $staff);
$router->get('/admin/leads/{id}', [LeadController::class, 'show'], $staff);
$router->post('/admin/leads/{id}', [LeadController::class, 'update'], $staff);
$router->post('/admin/leads/{id}/status', [LeadController::class, 'status'], $staff);
$router->post('/admin/leads/{id}/note', [LeadController::class, 'note'], $staff);

$router->get('/admin/assets', [AssetController::class, 'index'], $everyone);
$router->post('/admin/assets', [AssetController::class, 'upload'], $staff);
$router->get('/admin/assets/{id}', [AssetController::class, 'show'], $everyone);
$router->post('/admin/assets/{id}', [AssetController::class, 'update'], $staff);
$router->post('/admin/assets/{id}/attach', [AssetController::class, 'attach'], $staff);
$router->post('/admin/assets/{id}/delete', [AssetController::class, 'destroy'], $staff);

$router->get('/admin/settings', [SettingsController::class, 'index'], $everyone);
$router->post('/admin/settings/company', [SettingsController::class, 'company'], ['admin']);
$router->post('/admin/settings/users', [SettingsController::class, 'addUser'], ['admin']);
$router->post('/admin/settings/users/{id}', [SettingsController::class, 'updateUser'], ['admin']);
$router->post('/admin/settings/password', [SettingsController::class, 'password'], $everyone);
$router->post('/admin/orders/{id}', [SettingsController::class, 'orderStatus'], $staff);

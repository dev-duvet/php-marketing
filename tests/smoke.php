<?php
declare(strict_types=1);

/*
 * End-to-end smoke test against a running server (demo data must be loaded).
 *   php tests/smoke.php http://localhost:8000 <demo-password>
 * Walks every public and dashboard page and runs the main workflows over HTTP.
 */

$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$password = $argv[2] ?? getenv('DEMO_PASSWORD') ?: 'CreateZA-demo-2026';
$cookies = [];
$pass = 0;
$fail = 0;

function http(string $method, string $path, array $data = [], array $headers = []): array
{
    global $base, $cookies;
    $cookieHeader = $cookies ? 'Cookie: ' . implode('; ', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($cookies), $cookies)) : '';
    $opts = ['http' => [
        'method' => $method,
        'ignore_errors' => true,
        'follow_location' => 0,
        'header' => implode("\r\n", array_filter([$cookieHeader, ...$headers, $method === 'POST' ? 'Content-Type: application/x-www-form-urlencoded' : ''])),
        'content' => $method === 'POST' ? http_build_query($data) : null,
    ]];
    $body = @file_get_contents($base . $path, false, stream_context_create($opts));
    $status = 0;
    $location = null;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+ (\d+)#', $h, $m)) {
            $status = (int) $m[1];
        } elseif (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $h, $m)) {
            $cookies[$m[1]] = $m[2];
        } elseif (preg_match('/^Location: (.+)$/i', $h, $m)) {
            $location = trim($m[1]);
        }
    }
    return ['status' => $status, 'body' => (string) $body, 'location' => $location];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  \033[32m✓\033[0m " : "  \033[31m✗\033[0m ") . $label . ($ok || $detail === '' ? '' : " — {$detail}") . PHP_EOL;
}

function token(string $html): string
{
    preg_match('/name="_token" value="([^"]+)"/', $html, $m);
    return $m[1] ?? '';
}

function page(string $path, int $expect = 200): string
{
    $res = http('GET', $path);
    check("GET {$path} → {$expect}", $res['status'] === $expect, "got {$res['status']}");
    if ($expect === 200 && preg_match('/(Fatal error|Warning:|Notice:|Deprecated:|Uncaught)/', $res['body'], $m)) {
        check("{$path} renders without PHP errors", false, $m[1]);
    }
    return $res['body'];
}

echo "Public site" . PHP_EOL;
$home = page('/');
foreach (['Home', 'About', 'Services', 'Gallery', 'Work', 'Store', 'Events', 'Contact'] as $label) {
    check("nav has {$label}", str_contains($home, ">{$label}</a>"));
}
check('hero headline present', str_contains($home, 'Creative work<br>made to move'));
foreach (['/about', '/services', '/gallery', '/gallery?category=video', '/work', '/store', '/events', '/contact', '/store/cart'] as $p) {
    page($p);
}
page('/work/brand-content-refresh');
page('/store/createza-cap');
page('/events/sunset-creative-meet');
page('/does-not-exist', 404);
$health = http('GET', '/health');
check('/health reports ok', $health['status'] === 200 && str_contains($health['body'], '"status":"ok"'));

// Every local image referenced by the home page must exist
preg_match_all('/(?:src|href)="(\/assets\/[^"?]+)/', $home, $m);
$missing = array_filter(array_unique($m[1]), fn ($src) => http('GET', $src)['status'] !== 200);
check('all home page assets load', !$missing, implode(', ', $missing));

echo PHP_EOL . "Forms" . PHP_EOL;
$contact = page('/contact');
$bad = http('POST', '/contact', ['_token' => 'wrong']);
check('CSRF protection rejects bad token', $bad['status'] === 419);
$res = http('POST', '/contact', [
    '_token' => token($contact), 'name' => 'Smoke Test Lead', 'email' => 'smoke@example.com', 'service' => 'photography',
    'budget' => 'Not sure yet', 'details' => 'Smoke test enquiry', 'website' => '',
]);
check('contact form redirects on success', $res['status'] === 302 && str_contains((string) $res['location'], 'sent=1'));

$event = page('/events/sunset-creative-meet');
$res = http('POST', '/events/sunset-creative-meet/register', ['_token' => token($event), 'name' => 'Smoke Attendee', 'email' => 'smoke' . time() . '@example.com', 'guests' => '1']);
check('event registration succeeds', $res['status'] === 302 && ($after = page('/events/sunset-creative-meet')) && str_contains($after, 'You’re registered'));

$product = page('/store/createza-cap');
http('POST', '/store/cart/add', ['_token' => token($product), 'product_id' => '1', 'quantity' => '2']);
$cart = page('/store/cart');
check('cart shows added product', str_contains($cart, 'CreateZA Cap') && str_contains($cart, 'R560'));
http('POST', '/store/cart/update', ['_token' => token($cart), 'quantities[1]' => '3']);
check('cart quantity updates', str_contains(page('/store/cart'), 'R840'));
$checkout = page('/store/checkout');
check('checkout labels payment as coming soon', str_contains($checkout, 'Payment integration coming soon'));
$res = http('POST', '/store/checkout', ['_token' => token($checkout), 'customer_name' => 'Smoke Buyer', 'email' => 'buyer@example.com', 'address' => '1 Test St', 'city' => 'Cape Town', 'postal_code' => '8001']);
check('checkout creates an order', $res['status'] === 302 && str_contains((string) $res['location'], '/store/order/CZA-'));
$confirmation = page(parse_url((string) $res['location'], PHP_URL_PATH));
check('order confirmation shows total', str_contains($confirmation, 'R840'));

echo PHP_EOL . "Dashboard" . PHP_EOL;
$res = http('GET', '/admin');
check('dashboard requires login', $res['status'] === 302 && str_contains((string) $res['location'], '/admin/login'));
$login = page('/admin/login');
$res = http('POST', '/admin/login', ['_token' => token($login), 'email' => 'admin@createza.test', 'password' => 'wrong-password']);
check('wrong password is rejected', $res['status'] === 302 && str_contains((string) $res['location'], '/admin/login'));
$login = page('/admin/login');
$res = http('POST', '/admin/login', ['_token' => token($login), 'email' => 'admin@createza.test', 'password' => $password]);
check('admin can sign in', $res['status'] === 302 && ($res['location'] === '/admin' || str_ends_with((string) $res['location'], '/admin')));

$overview = page('/admin');
foreach (['Overview', 'Content calendar', 'Projects', 'Leads', 'Approvals', 'Asset library', 'Analytics', 'Settings'] as $label) {
    check("dashboard nav has {$label}", str_contains($overview, "<span>{$label}</span>"));
}
foreach (['/admin/content', '/admin/content/new', '/admin/projects', '/admin/projects/new', '/admin/projects/1', '/admin/projects/1/edit',
    '/admin/leads', '/admin/leads/new', '/admin/leads/1', '/admin/assets', '/admin/assets/1', '/admin/analytics', '/admin/settings',
    '/admin/content/1/edit', '/admin/content?week=2030-01-07', '/admin/assets?type=image&tag=studio'] as $p) {
    page($p);
}
$res = http('GET', '/admin/approvals');
check('approvals opens the first item in the queue', $res['status'] === 302 && str_contains((string) $res['location'], '/admin/approvals/'));
page(parse_url((string) $res['location'], PHP_URL_PATH));

$leads = page('/admin/leads');
check('website enquiry appears as a lead', str_contains($leads, 'Smoke Test Lead'));
preg_match('/href="\/admin\/leads\/(\d+)"><strong>Smoke Test Lead/', $leads, $m);
$leadId = $m[1] ?? '0';
$res = http('POST', "/admin/leads/{$leadId}/status", ['status' => 'discovery'], ['Accept: application/json', 'X-CSRF-Token: ' . token($leads)]);
check('kanban move via JSON works', $res['status'] === 200 && str_contains($res['body'], '"ok":true'));
$lead = page("/admin/leads/{$leadId}");
http('POST', "/admin/leads/{$leadId}/note", ['_token' => token($lead), 'body' => 'Called — smoke test note']);
check('lead notes are logged', str_contains(page("/admin/leads/{$leadId}"), 'smoke test note'));

$form = page('/admin/content/new');
$res = http('POST', '/admin/content', ['_token' => token($form), 'title' => 'Smoke content', 'platforms' => ['instagram', 'linkedin'], 'format' => 'Photo', 'status' => 'draft', 'publish_at' => date('Y-m-d') . 'T15:00', 'action' => 'submit']);
check('content is created and sent for approval', $res['status'] === 302 && preg_match('#/admin/approvals/(\d+)#', (string) $res['location'], $cm) === 1);
$contentId = $cm[1] ?? '0';
$approval = page("/admin/approvals/{$contentId}");
http('POST', "/admin/approvals/{$contentId}/comment", ['_token' => token($approval), 'body' => 'Looks good from smoke test']);
http('POST', "/admin/approvals/{$contentId}/decide", ['_token' => token($approval), 'decision' => 'approved']);
$approved = page("/admin/approvals/{$contentId}");
check('content approved with comment and history', str_contains($approved, 'status--approved') && str_contains($approved, 'Looks good from smoke test'));

$pform = page('/admin/projects/new');
$res = http('POST', '/admin/projects', ['_token' => token($pform), 'name' => 'Smoke Project', 'new_client' => 'Smoke Client', 'status' => 'planning', 'progress' => '10', 'members' => ['1']]);
check('project is created', $res['status'] === 302 && preg_match('#/admin/projects/(\d+)#', (string) $res['location'], $pm) === 1);
$projectId = $pm[1] ?? '0';
$edit = page("/admin/projects/{$projectId}/edit");
http('POST', "/admin/projects/{$projectId}", ['_token' => token($edit), 'name' => 'Smoke Project', 'status' => 'in_progress', 'progress' => '55']);
check('project is updated', str_contains(page("/admin/projects/{$projectId}"), '55%'));

$settings = page('/admin/settings');
$res = http('POST', '/admin/analytics', ['_token' => token($settings), 'period' => date('Y-m'), 'metrics' => ['instagram' => ['posts_published' => '9', 'reach' => '4321', 'engagement_rate' => '3.3', 'top_format' => 'Reel']]]);
check('analytics values save', $res['status'] === 302 && str_contains(page('/admin/analytics'), '4321'));

$res = http('POST', '/admin/logout', ['_token' => token($settings)]);
check('logout works', $res['status'] === 302 && http('GET', '/admin')['status'] === 302);

echo PHP_EOL . "Client role" . PHP_EOL;
$login = page('/admin/login');
http('POST', '/admin/login', ['_token' => token($login), 'email' => 'client@createza.test', 'password' => $password]);
page('/admin');
page('/admin/leads', 403);
page('/admin/analytics', 403);
page('/admin/content/new', 403);
$clientProjects = page('/admin/projects');
check('client only sees their own projects', str_contains($clientProjects, 'Brand content refresh') && !str_contains($clientProjects, 'Product launch content'));

echo PHP_EOL . "{$pass} passed, {$fail} failed" . PHP_EOL;
exit($fail > 0 ? 1 : 0);

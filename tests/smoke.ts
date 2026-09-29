/**
 * End-to-end smoke test against a running site with demo data.
 *   node tests/smoke.ts http://localhost:4321 [demo-password]
 * Walks every public and dashboard page and runs the main workflows over HTTP.
 */
import { readFileSync } from 'node:fs';

const base = (process.argv[2] ?? 'http://localhost:4321').replace(/\/$/, '');
const password = process.argv[3] ?? process.env.DEMO_PASSWORD ?? 'CreateZA-demo-2026';
const jar = new Map<string, string>();
let pass = 0;
let fail = 0;

interface Res {
  status: number;
  body: string;
  location: string;
}

async function http(method: string, path: string, body?: URLSearchParams | FormData, headers: Record<string, string> = {}): Promise<Res> {
  const res = await fetch(base + path, {
    method,
    body,
    redirect: 'manual',
    headers: { Origin: base, Cookie: [...jar].map(([k, v]) => `${k}=${v}`).join('; '), ...headers },
  });
  for (const c of res.headers.getSetCookie()) {
    const [pair] = c.split(';');
    const [k, v] = pair.split('=');
    if (v) jar.set(k, v);
    else jar.delete(k);
  }
  const ct = res.headers.get('content-type') ?? '';
  const text = ct.startsWith('image/') ? '' : await res.text();
  return { status: res.status, body: text, location: res.headers.get('location') ?? '' };
}

function check(label: string, ok: boolean, detail = ''): void {
  ok ? pass++ : fail++;
  console.log(`${ok ? '  \x1b[32m✓\x1b[0m' : '  \x1b[31m✗\x1b[0m'} ${label}${ok || !detail ? '' : ` — ${detail}`}`);
}

const token = (html: string) => /name="_token" value="([^"]+)"/.exec(html)?.[1] ?? '';
const form = (fields: Record<string, string | string[]>) => {
  const p = new URLSearchParams();
  for (const [k, v] of Object.entries(fields)) for (const x of [v].flat()) p.append(k, x);
  return p;
};

async function page(path: string, expect = 200): Promise<string> {
  const res = await http('GET', path);
  check(`GET ${path} → ${expect}`, res.status === expect, `got ${res.status}`);
  return res.body;
}

console.log('Public site');
const home = await page('/');
for (const label of ['Home', 'About', 'Services', 'Gallery', 'Work', 'Store', 'Events', 'Contact']) check(`nav has ${label}`, home.includes(`>${label}</a>`));
check('hero headline present', home.includes('Creative work<br>made to move') || home.includes('Creative work<br />made to move'));
for (const p of ['/about', '/services', '/gallery', '/gallery?category=video', '/work', '/store', '/events', '/contact', '/store/cart', '/work/brand-content-refresh', '/store/createza-cap', '/events/sunset-creative-meet']) await page(p);
await page('/does-not-exist', 404);
const health = await http('GET', '/health');
check('/health reports ok', health.status === 200 && health.body.includes('"status":"ok"'), health.body);
const assetPaths = [...new Set([...home.matchAll(/(?:src|href)="(\/assets\/[^"?]+)/g)].map((m) => m[1]))];
const missing = [];
for (const src of assetPaths) if ((await fetch(base + src)).status !== 200) missing.push(src);
check(`all ${assetPaths.length} home page assets load`, missing.length === 0, missing.join(', '));

console.log('\nForms');
let html = await page('/contact');
check('CSRF protection rejects bad token', (await http('POST', '/contact', form({ _token: 'wrong' }))).status === 419);
let res = await http('POST', '/contact', form({ _token: token(html), name: 'Smoke Test Lead', email: 'smoke@example.com', service: 'photography', budget: 'Not sure yet', details: 'Smoke test enquiry', website: '' }));
check('contact form redirects on success', res.status === 303 && res.location.includes('sent=1'), `${res.status} ${res.location}`);
check('contact success message shows', (await page('/contact?sent=1')).includes('your enquiry is in'));

html = await page('/events/sunset-creative-meet');
res = await http('POST', '/events/sunset-creative-meet', form({ _token: token(html), name: 'Smoke Attendee', email: `smoke${Date.now()}@example.com`, guests: '1' }));
check('event registration succeeds', res.status === 303 && (await page('/events/sunset-creative-meet')).includes('You’re registered'));

html = await page('/store/createza-cap');
await http('POST', '/store/cart', form({ _token: token(html), action: 'add', product_id: '1', quantity: '2' }));
html = await page('/store/cart');
check('cart shows added product', html.includes('CreateZA Cap') && html.includes('R560'));
await http('POST', '/store/cart', form({ _token: token(html), qty_1: '3' }));
check('cart quantity updates', (await page('/store/cart')).includes('R840'));
html = await page('/store/checkout');
check('checkout labels payment as coming soon', html.includes('Payment integration coming soon'));
res = await http('POST', '/store/checkout', form({ _token: token(html), customer_name: 'Smoke Buyer', email: 'buyer@example.com', address: '1 Test St', city: 'Cape Town', postal_code: '8001' }));
check('checkout creates an order', res.status === 303 && res.location.includes('/store/order/CZA-'), res.location);
check('order confirmation shows total', (await page(new URL(res.location, base).pathname)).includes('R840'));

console.log('\nDashboard');
res = await http('GET', '/admin');
check('dashboard requires login', res.status === 302 && res.location.includes('/admin/login'));
html = await page('/admin/login');
res = await http('POST', '/admin/login', form({ _token: token(html), email: 'admin@createza.test', password: 'wrong-password' }));
check('wrong password is rejected', res.location.endsWith('/admin/login'));
html = await page('/admin/login');
res = await http('POST', '/admin/login', form({ _token: token(html), email: 'admin@createza.test', password }));
check('admin can sign in', res.status === 303 && new URL(res.location, base).pathname === '/admin', res.location);

const overview = await page('/admin');
for (const label of ['Overview', 'Content calendar', 'Projects', 'Leads', 'Approvals', 'Asset library', 'Analytics', 'Settings']) check(`dashboard nav has ${label}`, overview.includes(`<span>${label}</span>`));
for (const p of ['/admin/content', '/admin/content/new', '/admin/projects', '/admin/projects/new', '/admin/projects/1', '/admin/projects/1/edit', '/admin/leads', '/admin/leads/new', '/admin/leads/1', '/admin/assets', '/admin/assets/1', '/admin/analytics', '/admin/settings', '/admin/content/1/edit', '/admin/content?week=2030-01-07', '/admin/assets?type=image&tag=studio']) await page(p);
res = await http('GET', '/admin/approvals');
check('approvals opens the first queued item', res.status === 302 && res.location.includes('/admin/approvals/'));
await page(new URL(res.location, base).pathname);

const leads = await page('/admin/leads');
check('website enquiry appears as a lead', leads.includes('Smoke Test Lead'));
const leadId = /href="\/admin\/leads\/(\d+)"><strong>Smoke Test Lead/.exec(leads)?.[1] ?? '0';
res = await http('POST', `/admin/leads/${leadId}/status`, form({ status: 'discovery' }), { Accept: 'application/json', 'X-CSRF-Token': token(leads) });
check('kanban move via JSON works', res.status === 200 && res.body.includes('"ok":true'), res.body);
html = await page(`/admin/leads/${leadId}`);
await http('POST', `/admin/leads/${leadId}`, form({ _token: token(html), action: 'note', body: 'Called — smoke test note' }));
check('lead notes are logged', (await page(`/admin/leads/${leadId}`)).includes('smoke test note'));

html = await page('/admin/content/new');
const today = new Date().toISOString().slice(0, 10);
res = await http('POST', '/admin/content/new', form({ _token: token(html), title: 'Smoke content', 'platforms[]': ['instagram', 'linkedin'], format: 'Photo', status: 'draft', publish_at: `${today}T15:00`, action: 'submit' }));
const contentId = /\/admin\/approvals\/(\d+)/.exec(res.location)?.[1] ?? '0';
check('content is created and sent for approval', res.status === 303 && contentId !== '0', `${res.status} ${res.location}`);
html = await page(`/admin/approvals/${contentId}`);
await http('POST', `/admin/approvals/${contentId}`, form({ _token: token(html), action: 'comment', body: 'Looks good from smoke test' }));
await http('POST', `/admin/approvals/${contentId}`, form({ _token: token(html), decision: 'approved' }));
html = await page(`/admin/approvals/${contentId}`);
check('content approved with comment and history', html.includes('status--approved') && html.includes('Looks good from smoke test'));

html = await page('/admin/projects/new');
res = await http('POST', '/admin/projects/new', form({ _token: token(html), name: 'Smoke Project', new_client: 'Smoke Client', status: 'planning', progress: '10', 'members[]': ['1'] }));
const projectId = /\/admin\/projects\/(\d+)/.exec(res.location)?.[1] ?? '0';
check('project is created', res.status === 303 && projectId !== '0', res.location);
html = await page(`/admin/projects/${projectId}/edit`);
await http('POST', `/admin/projects/${projectId}/edit`, form({ _token: token(html), name: 'Smoke Project', status: 'in_progress', progress: '55' }));
check('project is updated', (await page(`/admin/projects/${projectId}`)).includes('55%'));

html = await page('/admin/assets');
const upload = new FormData();
upload.append('_token', token(html));
upload.append('tags', 'smoke');
upload.append('files', new Blob([readFileSync('public/assets/media/g-sax.jpg')], { type: 'image/jpeg' }), 'smoke-upload.jpg');
res = await http('POST', '/admin/assets', upload);
html = await page('/admin/assets?tag=smoke');
const uploaded = /src="\/(uploads\/[a-f0-9]{32}\.jpg)"/.exec(html)?.[1];
check('asset upload is stored', res.status === 303 && Boolean(uploaded), `${res.status}`);
if (uploaded) {
  const file = await fetch(`${base}/${uploaded}`);
  check('uploaded file is served back', file.status === 200 && file.headers.get('content-type') === 'image/jpeg' && (await file.arrayBuffer()).byteLength === readFileSync('public/assets/media/g-sax.jpg').length);
}
const bad = new FormData();
bad.append('_token', token(html));
bad.append('files', new Blob(['<?php system($_GET[1]); ?>'], { type: 'image/jpeg' }), 'evil.jpg');
await http('POST', '/admin/assets', bad);
check('disguised script upload is rejected', (await page('/admin/assets')).includes('not allowed'));

html = await page('/admin/analytics');
const period = new Date().toISOString().slice(0, 7);
res = await http('POST', '/admin/analytics', form({ _token: token(html), period, instagram_posts_published: '9', instagram_reach: '4321', instagram_engagement_rate: '3.3', instagram_top_format: 'Reel' }));
check('analytics values save', res.status === 303 && (await page(`/admin/analytics?period=${period}`)).includes('4321'));

html = await page('/admin/settings');
res = await http('POST', '/admin/logout', form({ _token: token(html) }));
check('logout works', res.status === 303 && (await http('GET', '/admin')).status === 302);

console.log('\nClient role');
html = await page('/admin/login');
await http('POST', '/admin/login', form({ _token: token(html), email: 'client@createza.test', password }));
await page('/admin');
await page('/admin/leads', 403);
await page('/admin/analytics', 403);
await page('/admin/content/new', 403);
const clientProjects = await page('/admin/projects');
check('client only sees their own projects', clientProjects.includes('Brand content refresh') && !clientProjects.includes('Product launch content'));

console.log(`\n${pass} passed, ${fail} failed`);
process.exit(fail > 0 ? 1 : 0);

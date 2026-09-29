/**
 * Unit tests against a fresh in-memory SQLite database seeded with demo data.
 *   npm test
 */
process.env.DB_PATH = ':memory:';
process.env.DEMO_PASSWORD = 'test-password-123';
delete process.env.DATABASE_URL;
delete process.env.POSTGRES_URL;

import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';

const { ensureReady, count, one, run, val } = await import('../src/lib/db.ts');
const { attemptLogin, clientScope, MAX_ATTEMPTS } = await import('../src/lib/auth.ts');
const { verifyPassword } = await import('../src/lib/password.ts');
const { createLead, moveLead } = await import('../src/lib/leads.ts');
const { saveContent, submitContent, reviewContent } = await import('../src/lib/content.ts');
const { saveProject } = await import('../src/lib/projects.ts');
const { sniff } = await import('../src/lib/upload.ts');
const { placeOrder, cartSummary, registerForEvent } = await import('../src/lib/shop.ts');

before(async () => {
  await ensureReady();
});

const userId = async (email: string) => Number(await val('SELECT id FROM users WHERE email = ?', [email]));

describe('authentication', () => {
  test('passwords are stored as scrypt hashes', async () => {
    const hash = String(await val('SELECT password_hash FROM users WHERE email = ?', ['admin@createza.test']));
    assert.match(hash, /^scrypt\$/);
    assert.ok(await verifyPassword('test-password-123', hash));
  });

  test('correct credentials sign in (email is case-insensitive)', async () => {
    const result = await attemptLogin('ADMIN@CreateZA.test', 'test-password-123', '10.0.0.1');
    assert.equal(result.user?.role, 'admin');
  });

  test('a wrong password is rejected and recorded', async () => {
    const result = await attemptLogin('admin@createza.test', 'nope', '10.0.0.2');
    assert.ok(result.error);
    assert.equal(await count('SELECT COUNT(*) FROM login_attempts WHERE ip = ?', ['10.0.0.2']), 1);
  });

  test('repeated failures lock the account', async () => {
    for (let i = 0; i < MAX_ATTEMPTS; i++) await attemptLogin('team@createza.test', 'bad', '10.0.0.3');
    const result = await attemptLogin('team@createza.test', 'test-password-123', '10.0.0.3');
    assert.match(result.error ?? '', /Too many/);
    await run('DELETE FROM login_attempts');
  });

  test('inactive users cannot sign in', async () => {
    await run('UPDATE users SET is_active = 0 WHERE email = ?', ['designer@createza.test']);
    assert.ok((await attemptLogin('designer@createza.test', 'test-password-123', '10.0.0.4')).error);
    await run('UPDATE users SET is_active = 1 WHERE email = ?', ['designer@createza.test']);
  });

  test('client users are scoped to their own client', async () => {
    const client = await one('SELECT * FROM users WHERE role = ?', ['client']);
    const [sql, params] = clientScope(client as any);
    assert.match(sql, /client_id = \?/);
    assert.deepEqual(params, [Number(client!.client_id)]);
  });
});

describe('leads', () => {
  const enquiry = {
    name: 'Test Person', email: 'test@example.com', phone: '+27 11 000 0000', organisation: 'Test Org',
    service: 'videography', budget: 'R5 000 – R15 000', details: 'We need a launch video.', preferred_start: '2030-01-15',
  };

  test('a contact form enquiry creates a new lead with an activity entry', async () => {
    const id = await createLead(enquiry, 'website');
    const lead = await one('SELECT * FROM leads WHERE id = ?', [id]);
    assert.equal(lead!.status, 'new');
    assert.equal(lead!.source, 'website');
    assert.equal(await count('SELECT COUNT(*) FROM lead_activities WHERE lead_id = ?', [id]), 1);
  });

  test('invalid enquiries are rejected', async () => {
    await assert.rejects(createLead({ ...enquiry, name: '' }));
    await assert.rejects(createLead({ ...enquiry, email: 'not-an-email' }));
    await assert.rejects(createLead({ ...enquiry, service: 'unknown' }));
    await assert.rejects(createLead({ ...enquiry, details: '' }));
  });

  test('moving a lead logs the stage change', async () => {
    const id = await createLead(enquiry);
    await moveLead(id, 'proposal');
    assert.equal(await val('SELECT status FROM leads WHERE id = ?', [id]), 'proposal');
    assert.equal(await count('SELECT COUNT(*) FROM lead_activities WHERE lead_id = ?', [id]), 2);
    await assert.rejects(moveLead(id, 'bogus'));
  });
});

describe('content and approvals', () => {
  test('content is created with valid platforms and attached assets', async () => {
    const producer = await userId('team@createza.test');
    const assetId = Number(await val('SELECT id FROM assets ORDER BY id LIMIT 1'));
    const id = await saveContent(
      { title: 'Test reel', format: 'Reel', caption: 'Hi', platforms: ['instagram', 'tiktok', 'myspace'], publish_at: '2030-02-01T10:00', asset_ids: [String(assetId)] },
      null,
      producer,
    );
    assert.equal(await val('SELECT publish_at FROM content_items WHERE id = ?', [id]), '2030-02-01 10:00');
    assert.equal(await count('SELECT COUNT(*) FROM content_platforms WHERE content_id = ?', [id]), 2);
    assert.equal(await count('SELECT COUNT(*) FROM content_assets WHERE content_id = ?', [id]), 1);
  });

  test('content needs a title and a platform', async () => {
    const producer = await userId('team@createza.test');
    await assert.rejects(saveContent({ title: '', platforms: ['instagram'] }, null, producer));
    await assert.rejects(saveContent({ title: 'x', platforms: [] }, null, producer));
  });

  test('saving cannot jump straight to awaiting approval', async () => {
    const producer = await userId('team@createza.test');
    const id = await saveContent({ title: 'Sneaky', platforms: ['instagram'], status: 'awaiting_approval' }, null, producer);
    assert.equal(await val('SELECT status FROM content_items WHERE id = ?', [id]), 'draft');
  });

  test('content moves through the approval workflow with versions', async () => {
    const producer = await userId('team@createza.test');
    const client = await userId('client@createza.test');
    const id = await saveContent({ title: 'Approval test', platforms: ['instagram'] }, null, producer);
    await submitContent(id, producer);
    assert.equal(await val('SELECT status FROM content_items WHERE id = ?', [id]), 'awaiting_approval');
    await assert.rejects(reviewContent(id, 'changes_requested', client, ''));
    await reviewContent(id, 'changes_requested', client, 'Shorter please');
    assert.equal(await val('SELECT status FROM content_items WHERE id = ?', [id]), 'in_production');
    await submitContent(id, producer);
    assert.equal(Number(await val('SELECT version FROM content_items WHERE id = ?', [id])), 2);
    await reviewContent(id, 'approved', client);
    assert.equal(await val('SELECT status FROM content_items WHERE id = ?', [id]), 'approved');
    assert.equal(await count('SELECT COUNT(*) FROM content_approvals WHERE content_id = ?', [id]), 4);
    await assert.rejects(reviewContent(id, 'approved', client));
  });
});

describe('projects', () => {
  test('a project is created with a new client and staff-only members', async () => {
    const team = (await Promise.all(['admin@createza.test', 'team@createza.test'].map(userId))).map(String);
    const client = String(await userId('client@createza.test'));
    const id = await saveProject({ name: 'Test Project', new_client: 'New Client Co', status: 'planning', progress: '150', members: [...team, client] });
    const p = await one('SELECT p.*, c.name AS client FROM projects p JOIN clients c ON c.id = p.client_id WHERE p.id = ?', [id]);
    assert.equal(p!.client, 'New Client Co');
    assert.equal(p!.slug, 'test-project');
    assert.equal(Number(p!.progress), 100);
    assert.equal(await count('SELECT COUNT(*) FROM project_members WHERE project_id = ?', [id]), 2);
  });

  test('slugs stay unique and projects can be updated', async () => {
    const a = await saveProject({ name: 'Same Name' });
    const b = await saveProject({ name: 'Same Name' });
    assert.notEqual(await val('SELECT slug FROM projects WHERE id = ?', [a]), await val('SELECT slug FROM projects WHERE id = ?', [b]));
    await saveProject({ name: 'After', status: 'completed', progress: '100' }, a);
    assert.equal(await val('SELECT status FROM projects WHERE id = ?', [a]), 'completed');
  });

  test('invalid projects are rejected', async () => {
    await assert.rejects(saveProject({ name: '' }));
    await assert.rejects(saveProject({ name: 'x', status: 'nonsense' }));
    await assert.rejects(saveProject({ name: 'x' }, 999999));
  });
});

describe('store, events and uploads', () => {
  test('orders snapshot prices and reduce stock', async () => {
    const cart = { '1': 2 };
    assert.equal((await cartSummary(cart)).subtotal, 56000);
    const ref = await placeOrder({ customer_name: 'Buyer', email: 'b@example.com', address: '1 St', city: 'Cape Town', postal_code: '8001' }, cart);
    const order = await one('SELECT * FROM orders WHERE reference = ?', [ref]);
    assert.equal(Number(order!.subtotal_cents), 56000);
    assert.equal(Number(await val('SELECT stock FROM products WHERE id = 1')), 23);
  });

  test('event registration enforces capacity and duplicates', async () => {
    await registerForEvent('sunset-creative-meet', { name: 'A', email: 'a@example.com', guests: '2' });
    await assert.rejects(registerForEvent('sunset-creative-meet', { name: 'A', email: 'A@example.com' }));
    await assert.rejects(registerForEvent('community-shoot-day', { name: 'B', email: 'b@example.com' }), /closed/);
  });

  test('uploads are identified by content, not name', () => {
    assert.equal(sniff(new Uint8Array([0xff, 0xd8, 0xff, 0xe0]))?.mime, 'image/jpeg');
    assert.equal(sniff(new TextEncoder().encode('%PDF-1.7'))?.type, 'document');
    assert.equal(sniff(new TextEncoder().encode('<?php echo 1;')), null);
    assert.equal(sniff(new TextEncoder().encode('<svg onload=alert(1)>')), null);
  });
});

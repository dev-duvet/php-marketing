/**
 * Loads clearly labelled DEMO data. Client names, figures and results are placeholders —
 * replace them with real records before going live.
 */
import { setSetting } from './app.ts';
import { LEAD_STATUSES, PLATFORMS } from './catalog.ts';
import { insert, one, run } from './db.ts';
import { env } from './env.ts';
import { addDays, slugify, toDateString, toDateTimeString, today } from './format.ts';
import { addLeadActivity } from './leads.ts';
import { hashPassword } from './password.ts';

const day = (offset: number, time = '10:00') => `${addDays(today(), offset)} ${time}`;
const hoursAgo = (h: number) => toDateTimeString(new Date(Date.now() - h * 3_600_000));
const MEDIA_SIZES: Record<string, number> = {};

export async function seed(): Promise<void> {
  await settings();
  const clients = await seedClients();
  const users = await seedUsers(clients);
  const projects = await seedProjects(clients, users);
  const assets = await seedAssets(projects, users);
  await seedContent(projects, users, assets);
  await seedLeads(users);
  await seedEvents();
  await seedProducts();
  await seedAnalytics();
}

async function settings() {
  const values: Record<string, string> = {
    company_name: 'CreateZA',
    company_tagline: 'Creative work made to move.',
    company_email: 'hello@createza.co.za',
    company_phone: '+27 00 000 0000',
    company_address: 'South Africa',
    color_navy: '#14284B',
    color_red: '#C8161D',
    color_gold: '#C9A13B',
    logo_path: 'assets/brand/createza-logo.jpg',
    social_instagram: 'https://instagram.com/',
    social_tiktok: 'https://tiktok.com/',
    social_youtube: 'https://youtube.com/',
    social_x: 'https://x.com/',
    social_facebook: 'https://facebook.com/',
    social_linkedin: 'https://linkedin.com/',
    notify_new_lead: '1',
    notify_approval: '1',
    notify_order: '1',
  };
  for (const [k, v] of Object.entries(values)) await setSetting(k, v);
}

async function seedClients(): Promise<number[]> {
  const ids = [];
  for (const [name, contact_name, email] of [
    ['Demo Client — Coastal Café', 'Amani (demo)', 'cafe@example.com'],
    ['Demo Client — Launch Brand', 'Lebo (demo)', 'brand@example.com'],
    ['Demo Client — Community Org', 'Jomo (demo)', 'org@example.com'],
  ]) ids.push(await insert('clients', { name, contact_name, email }));
  return ids;
}

async function seedUsers(clients: number[]): Promise<Record<string, number>> {
  const hash = await hashPassword(env('DEMO_PASSWORD', 'CreateZA-demo-2026'));
  const users: Record<string, number> = {};
  const rows: [string, string, string, string, number | null, string][] = [
    ['admin', 'CreateZA Admin', 'admin@createza.test', 'admin', null, 'Studio lead'],
    ['producer', 'Thandi (demo)', 'team@createza.test', 'team', null, 'Content producer'],
    ['designer', 'Sipho (demo)', 'designer@createza.test', 'team', null, 'Designer & editor'],
    ['client', 'Amani (demo client)', 'client@createza.test', 'client', clients[0], 'Client reviewer'],
  ];
  for (const [key, name, email, role, client_id, title] of rows) {
    users[key] = await insert('users', { name, email, password_hash: hash, role, client_id, title });
  }
  return users;
}

async function seedProjects(clients: number[], users: Record<string, number>): Promise<number[]> {
  const rows: [string, number, string, string, number, number, number, string, string][] = [
    ['Brand content refresh', 0, 'social-media', 'in_progress', 70, -20, 8, 'assets/media/hero-studio.jpg', 'A refreshed set of photos, reels and templates for a café’s social channels.'],
    ['Product launch content', 1, 'videography', 'in_progress', 40, -10, 20, 'assets/media/video.jpg', 'Launch film, cut-downs and product stills for a new product release.'],
    ['Social media strategy', 2, 'content-strategy', 'planning', 20, -3, 30, 'assets/media/strategy.jpg', 'Audience research, content pillars and a 90-day calendar.'],
    ['Campaign creative', 1, 'photography', 'in_review', 85, -30, 4, 'assets/media/hero-camera.jpg', 'Studio and on-location photography for a seasonal campaign.'],
    ['Community website', 2, 'websites', 'completed', 100, -60, -5, 'assets/media/websites.jpg', 'A fast, accessible website with events and volunteer sign-ups.'],
  ];
  const ids = [];
  for (const [name, client, service, status, progress, start, due, cover, overview] of rows) {
    const id = await insert('projects', {
      name, slug: slugify(name), client_id: clients[client], description: overview, service_type: service,
      start_date: addDays(today(), start), due_date: addDays(today(), due), status, progress,
      deliverables: 'Shoot plan\nEdited photo set\nSocial cut-downs\nFinal handover',
      notes: 'Demo project — replace with a real brief.', is_public: 1, cover_image: cover,
      outcomes: 'Placeholder outcome: describe what the client received.\nPlaceholder outcome: add real results once measured.',
    });
    await run('INSERT INTO project_members (project_id, user_id) VALUES (?, ?), (?, ?)', [id, users.producer, id, users.designer]);
    ids.push(id);
  }
  return ids;
}

async function seedAssets(projects: number[], users: Record<string, number>): Promise<number[]> {
  const media: [string, string, number | null, string][] = [
    ['hero-studio', 'photography', 0, 'studio,portrait'], ['g-ringlight', 'bts', 0, 'studio,bts'],
    ['g-studio-pose', 'photography', 0, 'studio,portrait'], ['video', 'video', 1, 'video,crew'],
    ['g-crew', 'bts', 1, 'bts,crew'], ['hero-camera', 'photography', 3, 'portrait'],
    ['g-polaroid', 'photography', 3, 'portrait,bw'], ['g-bw-camera', 'bts', 3, 'bw,bts'],
    ['websites', 'websites', 4, 'web'], ['social', 'social', 2, 'social,mobile'],
    ['g-skater', 'social', 0, 'lifestyle'], ['g-store', 'social', 1, 'lifestyle'],
    ['g-home', 'social', null, 'lifestyle'], ['g-sax', 'events', null, 'music,bw'],
    ['events', 'events', 4, 'community'], ['g-volunteer', 'events', 4, 'community'],
    ['g-field', 'photography', null, 'outdoor'], ['g-forest', 'photography', null, 'outdoor,portrait'],
    ['g-garden', 'photography', null, 'outdoor,portrait'], ['g-vintage', 'video', null, 'camera'],
    ['g-ranch', 'video', null, 'outdoor'], ['g-heritage', 'photography', null, 'portrait'],
    ['g-mirror', 'social', null, 'portrait'], ['hero-street', 'bts', null, 'street,bts'],
    ['hero-creator', 'social', null, 'portrait'], ['hero-session', 'bts', null, 'studio,bts'],
    ['strategy', 'websites', 2, 'planning'], ['community', 'events', null, 'community'],
  ];
  const ids = [];
  for (const [i, [file, category, project, tags]] of media.entries()) {
    const title = file.replace(/^g-/, '').replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const id = await insert('assets', {
      title, path: `assets/media/${file}.jpg`, mime: 'image/jpeg', type: 'image', size: MEDIA_SIZES[file] ?? 180_000,
      project_id: project === null ? null : projects[project], public_category: category,
      uploaded_by: i % 2 ? users.producer : users.designer, created_at: hoursAgo(media.length - i),
    });
    for (const tag of tags.split(',')) {
      await run('INSERT INTO asset_tags (name) VALUES (?) ON CONFLICT DO NOTHING', [tag]);
      const tagRow = await one('SELECT id FROM asset_tags WHERE name = ?', [tag]);
      await run('INSERT INTO asset_tag_map (asset_id, tag_id) VALUES (?, ?)', [id, tagRow!.id]);
    }
    ids.push(id);
  }
  return ids;
}

async function seedContent(projects: number[], users: Record<string, number>, assets: number[]) {
  const items: [string, number | null, string, string[], number, string, string, number][] = [
    ['Café menu flat-lay', 0, 'Photo', ['instagram', 'facebook'], -2, '10:00', 'published', 0],
    ['Barista reel', 0, 'Reel', ['instagram', 'tiktok'], -1, '12:00', 'published', 1],
    ['Launch teaser', 1, 'Video', ['tiktok', 'youtube'], 0, '11:00', 'scheduled', 3],
    ['Behind the scenes carousel', 0, 'Carousel', ['instagram'], 1, '10:00', 'awaiting_approval', 2],
    ['Product close-ups', 1, 'Photo', ['facebook'], 1, '11:00', 'approved', 11],
    ['Founder interview short', 1, 'Short', ['youtube'], 2, '10:00', 'in_production', 4],
    ['Campaign hero image', 3, 'Graphic', ['linkedin', 'facebook'], 2, '14:00', 'awaiting_approval', 5],
    ['Weekend story set', 0, 'Story', ['instagram'], 3, '14:00', 'scheduled', 10],
    ['Launch film — full cut', 1, 'Video', ['youtube', 'facebook'], 3, '12:00', 'draft', 3],
    ['Strategy explainer article', 2, 'Article', ['linkedin'], 4, '10:00', 'draft', 26],
    ['Community day recap', null, 'Video', ['tiktok', 'instagram'], 5, '16:00', 'idea', 14],
    ['Portrait series', 3, 'Photo', ['instagram'], 5, '10:00', 'awaiting_approval', 6],
    ['Weekly tips graphic', 2, 'Graphic', ['linkedin', 'facebook'], 6, '10:00', 'draft', 9],
    ['Street style reel', null, 'Reel', ['tiktok'], 8, '15:00', 'idea', 23],
  ];
  for (const [title, project, format, platforms, offset, time, status, asset] of items) {
    const id = await insert('content_items', {
      title, project_id: project === null ? null : projects[project], format,
      caption: `Demo caption for “${title}”. Replace with the final copy and hashtags.`,
      publish_at: day(offset, time), status, assigned_to: offset % 2 ? users.producer : users.designer,
      internal_notes: 'Demo item.', created_by: users.producer,
    });
    for (const p of platforms) await run('INSERT INTO content_platforms (content_id, platform) VALUES (?, ?)', [id, p]);
    await run('INSERT INTO content_assets (content_id, asset_id) VALUES (?, ?)', [id, assets[asset]]);

    if (['awaiting_approval', 'approved', 'scheduled', 'published'].includes(status)) {
      await insert('content_approvals', {
        content_id: id, user_id: users.producer, action: 'submitted', version: 1,
        snapshot: JSON.stringify({ title, caption: 'First draft caption.', assets: [] }), created_at: hoursAgo(3),
      });
    }
    if (status === 'awaiting_approval') {
      await insert('content_comments', { content_id: id, user_id: users.client, body: 'This looks great! Can we try a shorter version for Instagram?', created_at: hoursAgo(2) });
      await insert('content_comments', { content_id: id, user_id: users.designer, body: 'Sure, I’ll create a tighter cut and share v2 shortly.', created_at: hoursAgo(1) });
    }
    if (['approved', 'scheduled', 'published'].includes(status)) {
      await insert('content_approvals', { content_id: id, user_id: users.client, action: 'approved', version: 1, created_at: hoursAgo(2) });
    }
  }
}

async function seedLeads(users: Record<string, number>) {
  const leads: [string, string, string, string, string][] = [
    ['Demo Lead — Website Refresh', 'social-media', 'new', 'R15 000 – R50 000', 'Looking for ongoing social content to support a website refresh.'],
    ['Demo Lead — Brand Campaign', 'videography', 'new', 'R50 000+', 'Video production for a brand campaign.'],
    ['Demo Lead — Product Launch', 'photography', 'new', 'R5 000 – R15 000', 'Design assets and product photography for a launch.'],
    ['Demo Lead — Event Coverage', 'event-coverage', 'contacted', 'R5 000 – R15 000', 'Photo and video coverage for a one-day event.'],
    ['Demo Lead — Always-on Content', 'social-media', 'contacted', 'R15 000 – R50 000', 'Monthly social media content.'],
    ['Demo Lead — Rebrand Project', 'content-strategy', 'discovery', 'R50 000+', 'Full creative support for a rebrand.'],
    ['Demo Lead — Training Videos', 'videography', 'discovery', 'R15 000 – R50 000', 'Series of short training videos.'],
    ['Demo Lead — Social Strategy', 'content-strategy', 'proposal', 'R5 000 – R15 000', 'Strategy and content calendar.'],
    ['Demo Lead — Campaign Assets', 'photography', 'proposal', 'R15 000 – R50 000', 'Design and video assets for a campaign.'],
    ['Demo Lead — Monthly Content', 'social-media', 'won', 'R15 000 – R50 000', 'Ongoing retainer for monthly content.'],
    ['Demo Lead — New Website', 'websites', 'onboarding', 'R15 000 – R50 000', 'Portfolio website build.'],
  ];
  for (const [i, [name, service, status, budget, details]] of leads.entries()) {
    const created = hoursAgo((i + 1) * 24);
    const id = await insert('leads', {
      name, email: `lead${i + 1}@example.com`, phone: `+27 00 000 00${String(i).padStart(2, '0')}`,
      organisation: `Demo organisation ${i + 1}`, service, budget, details, status,
      source: i % 3 ? 'website' : 'referral', follow_up_date: addDays(today(), (i % 5) + 1),
      assigned_to: i % 2 ? users.producer : users.admin, created_at: created, updated_at: created,
    });
    await addLeadActivity(id, 'Enquiry received (demo record).');
    if (status !== 'new') await addLeadActivity(id, `Moved from New to ${LEAD_STATUSES[status]}.`, users.admin);
  }
}

async function seedEvents() {
  const events: [string, string, string, number, string, number, string][] = [
    ['Sunset Creative Meet', 'Photo walk and social content session at golden hour.', 'Cape Town (demo venue)', 14, '17:00', 30, 'assets/media/hero-session.jpg'],
    ['Creative Workshop', 'Video, editing and storytelling for small brands.', 'Johannesburg (demo venue)', 35, '10:00', 20, 'assets/media/video.jpg'],
    ['City Content Day', 'Urban photography and reels, with feedback from the team.', 'Durban (demo venue)', 60, '09:00', 25, 'assets/media/hero-street.jpg'],
    ['Community Shoot Day', 'A past community shoot — kept here for the event archive.', 'Pretoria (demo venue)', -30, '09:00', 40, 'assets/media/events.jpg'],
  ];
  for (const [title, summary, location, offset, time, capacity, image] of events) {
    const id = await insert('events', {
      title, slug: slugify(title), summary, location, starts_at: day(offset, time), capacity, image,
      description: `${summary}\n\nBring your camera or phone. Spaces are limited, so register below. This is a demo event — update the details before publishing.`,
    });
    if (offset < 0) await insert('event_registrations', { event_id: id, name: 'Demo attendee', email: 'attendee@example.com', guests: 1 });
  }
}

async function seedProducts() {
  const products: [string, number, string, string, number][] = [
    ['CreateZA Cap', 28000, 'Structured cap with the CreateZA mark embroidered on the front.', 'assets/img/product-cap.svg', 25],
    ['CreateZA T-Shirt', 30000, 'Soft heavyweight cotton tee with a small chest logo.', 'assets/img/product-tee.svg', 40],
    ['CreateZA Hoodie', 65000, 'Midweight hoodie for early call times and late edits.', 'assets/img/product-hoodie.svg', 15],
    ['CreateZA Tote', 25000, 'Canvas tote that fits a camera body, lenses and a laptop.', 'assets/img/product-tote.svg', 30],
  ];
  for (const [name, price_cents, description, image, stock] of products) {
    await insert('products', { name, slug: slugify(name), price_cents, description, image, stock });
  }
}

async function seedAnalytics() {
  const platforms = Object.keys(PLATFORMS);
  const formats = ['Reel', 'Carousel', 'Short', 'Photo', 'Article'];
  const now = new Date();
  for (let m = 5; m >= 0; m--) {
    const period = toDateString(new Date(now.getFullYear(), now.getMonth() - m, 1)).slice(0, 7);
    for (const [p, platform] of platforms.entries()) {
      // Deterministic placeholder numbers — clearly demo, not real performance.
      const base = (6 - m) * 10 + p * 7;
      await insert('analytics_metrics', {
        period, platform, posts_published: 6 + (base % 9), reach: 1000 + base * 45,
        engagement_rate: Math.round((2 + ((base * 13) % 40) / 10) * 10) / 10, top_format: formats[p],
      });
    }
  }
}

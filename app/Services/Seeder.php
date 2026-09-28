<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Loads clearly labelled DEMO data. Client names, figures and results are placeholders —
 * replace them with real records before going live.
 */
final class Seeder
{
    public static function run(): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            self::settings();
            $clients = self::clients();
            $users = self::users($clients);
            $projects = self::projects($clients, $users);
            $assets = self::assets($projects, $users);
            self::content($projects, $users, $assets);
            self::leads($users);
            self::events();
            self::products();
            self::analytics();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function insert(string $table, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        q("INSERT INTO {$table} ({$cols}) VALUES ({$marks})", array_values($data));
        return (int) db()->lastInsertId();
    }

    private static function day(int $offset, string $time = '10:00'): string
    {
        return date('Y-m-d', strtotime("{$offset} days")) . ' ' . $time;
    }

    private static function settings(): void
    {
        $settings = [
            'company_name' => 'CreateZA',
            'company_tagline' => 'Creative work made to move.',
            'company_email' => 'hello@createza.co.za',
            'company_phone' => '+27 00 000 0000',
            'company_address' => 'South Africa',
            'color_navy' => '#14284B',
            'color_red' => '#C8161D',
            'color_gold' => '#C9A13B',
            'logo_path' => 'assets/brand/createza-logo.jpg',
            'social_instagram' => 'https://instagram.com/',
            'social_tiktok' => 'https://tiktok.com/',
            'social_youtube' => 'https://youtube.com/',
            'social_x' => 'https://x.com/',
            'social_facebook' => 'https://facebook.com/',
            'social_linkedin' => 'https://linkedin.com/',
            'notify_new_lead' => '1',
            'notify_approval' => '1',
            'notify_order' => '1',
        ];
        foreach ($settings as $key => $value) {
            set_setting($key, $value);
        }
    }

    private static function clients(): array
    {
        $ids = [];
        foreach ([
            ['Demo Client — Coastal Café', 'Amani (demo)', 'cafe@example.com'],
            ['Demo Client — Launch Brand', 'Lebo (demo)', 'brand@example.com'],
            ['Demo Client — Community Org', 'Jomo (demo)', 'org@example.com'],
        ] as [$name, $contact, $email]) {
            $ids[] = self::insert('clients', ['name' => $name, 'contact_name' => $contact, 'email' => $email]);
        }
        return $ids;
    }

    private static function users(array $clients): array
    {
        $password = password_hash(env('DEMO_PASSWORD', 'CreateZA-demo-2026'), PASSWORD_DEFAULT);
        $users = [];
        foreach ([
            'admin' => ['CreateZA Admin', 'admin@createza.test', 'admin', null, 'Studio lead'],
            'producer' => ['Thandi (demo)', 'team@createza.test', 'team', null, 'Content producer'],
            'designer' => ['Sipho (demo)', 'designer@createza.test', 'team', null, 'Designer & editor'],
            'client' => ['Amani (demo client)', 'client@createza.test', 'client', $clients[0], 'Client reviewer'],
        ] as $key => [$name, $email, $role, $clientId, $title]) {
            $users[$key] = self::insert('users', [
                'name' => $name, 'email' => $email, 'password_hash' => $password,
                'role' => $role, 'client_id' => $clientId, 'title' => $title,
            ]);
        }
        return $users;
    }

    private static function projects(array $clients, array $users): array
    {
        $rows = [
            ['Brand content refresh', 0, 'social-media', 'in_progress', 70, -20, 8, 'assets/media/hero-studio.jpg',
                'A refreshed set of photos, reels and templates for a café’s social channels.'],
            ['Product launch content', 1, 'videography', 'in_progress', 40, -10, 20, 'assets/media/video.jpg',
                'Launch film, cut-downs and product stills for a new product release.'],
            ['Social media strategy', 2, 'content-strategy', 'planning', 20, -3, 30, 'assets/media/strategy.jpg',
                'Audience research, content pillars and a 90-day calendar.'],
            ['Campaign creative', 1, 'photography', 'in_review', 85, -30, 4, 'assets/media/hero-camera.jpg',
                'Studio and on-location photography for a seasonal campaign.'],
            ['Community website', 2, 'websites', 'completed', 100, -60, -5, 'assets/media/websites.jpg',
                'A fast, accessible website with events and volunteer sign-ups.'],
        ];
        $ids = [];
        foreach ($rows as [$name, $client, $service, $status, $progress, $start, $due, $cover, $overview]) {
            $id = self::insert('projects', [
                'name' => $name, 'slug' => slugify($name), 'client_id' => $clients[$client],
                'description' => $overview, 'service_type' => $service,
                'start_date' => date('Y-m-d', strtotime("{$start} days")), 'due_date' => date('Y-m-d', strtotime("{$due} days")),
                'status' => $status, 'progress' => $progress,
                'deliverables' => "Shoot plan\nEdited photo set\nSocial cut-downs\nFinal handover",
                'notes' => 'Demo project — replace with a real brief.',
                'is_public' => 1, 'cover_image' => $cover,
                'outcomes' => "Placeholder outcome: describe what the client received.\nPlaceholder outcome: add real results once measured.",
            ]);
            q('INSERT INTO project_members (project_id, user_id) VALUES (?, ?), (?, ?)', [$id, $users['producer'], $id, $users['designer']]);
            $ids[] = $id;
        }
        return $ids;
    }

    private static function assets(array $projects, array $users): array
    {
        $media = [
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
        $ids = [];
        foreach ($media as $i => [$file, $category, $project, $tags]) {
            $path = "assets/media/{$file}.jpg";
            $full = BASE_PATH . '/public/' . $path;
            $id = self::insert('assets', [
                'title' => ucwords(str_replace(['g-', '-'], ['', ' '], $file)),
                'path' => $path, 'mime' => 'image/jpeg', 'type' => 'image',
                'size' => is_file($full) ? filesize($full) : 0,
                'project_id' => $project !== null ? $projects[$project] : null,
                'public_category' => $category,
                'uploaded_by' => $users[$i % 2 ? 'producer' : 'designer'],
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . (count($media) - $i) . ' hours')),
            ]);
            foreach (explode(',', $tags) as $tag) {
                q('INSERT INTO asset_tags (name) VALUES (?) ON CONFLICT DO NOTHING', [$tag]);
                $tagId = (int) scalar('SELECT id FROM asset_tags WHERE name = ?', [$tag]);
                q('INSERT INTO asset_tag_map (asset_id, tag_id) VALUES (?, ?)', [$id, $tagId]);
            }
            $ids[] = $id;
        }
        return $ids;
    }

    private static function content(array $projects, array $users, array $assets): void
    {
        // [title, project, format, platforms, day offset, time, status, asset index]
        $items = [
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
        foreach ($items as [$title, $project, $format, $platforms, $offset, $time, $status, $asset]) {
            $id = self::insert('content_items', [
                'title' => $title, 'project_id' => $project !== null ? $projects[$project] : null,
                'format' => $format, 'caption' => "Demo caption for “{$title}”. Replace with the final copy and hashtags.",
                'publish_at' => self::day($offset, $time), 'status' => $status,
                'assigned_to' => $users[$offset % 2 ? 'producer' : 'designer'],
                'internal_notes' => 'Demo item.', 'created_by' => $users['producer'],
            ]);
            foreach ($platforms as $platform) {
                q('INSERT INTO content_platforms (content_id, platform) VALUES (?, ?)', [$id, $platform]);
            }
            q('INSERT INTO content_assets (content_id, asset_id) VALUES (?, ?)', [$id, $assets[$asset]]);

            if (in_array($status, ['awaiting_approval', 'approved', 'scheduled', 'published'], true)) {
                self::insert('content_approvals', [
                    'content_id' => $id, 'user_id' => $users['producer'], 'action' => 'submitted', 'version' => 1,
                    'snapshot' => json_encode(['title' => $title, 'caption' => 'First draft caption.', 'assets' => []]),
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                ]);
            }
            if ($status === 'awaiting_approval') {
                foreach ([
                    [$users['client'], 'This looks great! Can we try a shorter version for Instagram?', '-2 hours'],
                    [$users['designer'], 'Sure, I’ll create a tighter cut and share v2 shortly.', '-1 hours'],
                ] as [$userId, $body, $when]) {
                    self::insert('content_comments', [
                        'content_id' => $id, 'user_id' => $userId, 'body' => $body,
                        'created_at' => date('Y-m-d H:i:s', strtotime($when)),
                    ]);
                }
            }
            if (in_array($status, ['approved', 'scheduled', 'published'], true)) {
                self::insert('content_approvals', [
                    'content_id' => $id, 'user_id' => $users['client'], 'action' => 'approved', 'version' => 1,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                ]);
            }
        }
    }

    private static function leads(array $users): void
    {
        $leads = [
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
        foreach ($leads as $i => [$name, $service, $status, $budget, $details]) {
            $id = self::insert('leads', [
                'name' => $name, 'email' => 'lead' . ($i + 1) . '@example.com', 'phone' => '+27 00 000 00' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'organisation' => 'Demo organisation ' . ($i + 1), 'service' => $service, 'budget' => $budget,
                'details' => $details, 'status' => $status, 'source' => $i % 3 ? 'website' : 'referral',
                'follow_up_date' => date('Y-m-d', strtotime(($i % 5 + 1) . ' days')),
                'assigned_to' => $users[$i % 2 ? 'producer' : 'admin'],
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . ($i + 1) . ' days')),
            ]);
            LeadService::addActivity($id, 'Enquiry received (demo record).');
            if ($status !== 'new') {
                LeadService::addActivity($id, 'Moved from New to ' . Catalog::LEAD_STATUSES[$status] . '.', $users['admin']);
            }
        }
    }

    private static function events(): void
    {
        $events = [
            ['Sunset Creative Meet', 'Photo walk and social content session at golden hour.', 'Cape Town (demo venue)', 14, '17:00', 30, 'assets/media/hero-session.jpg'],
            ['Creative Workshop', 'Video, editing and storytelling for small brands.', 'Johannesburg (demo venue)', 35, '10:00', 20, 'assets/media/video.jpg'],
            ['City Content Day', 'Urban photography and reels, with feedback from the team.', 'Durban (demo venue)', 60, '09:00', 25, 'assets/media/hero-street.jpg'],
            ['Community Shoot Day', 'A past community shoot — kept here for the event archive.', 'Pretoria (demo venue)', -30, '09:00', 40, 'assets/media/events.jpg'],
        ];
        foreach ($events as [$title, $summary, $location, $offset, $time, $capacity, $image]) {
            $id = self::insert('events', [
                'title' => $title, 'slug' => slugify($title), 'summary' => $summary,
                'description' => $summary . "\n\nBring your camera or phone. Spaces are limited, so register below. This is a demo event — update the details before publishing.",
                'location' => $location, 'starts_at' => self::day($offset, $time), 'capacity' => $capacity, 'image' => $image,
            ]);
            if ($offset < 0) {
                self::insert('event_registrations', ['event_id' => $id, 'name' => 'Demo attendee', 'email' => 'attendee@example.com', 'guests' => 1]);
            }
        }
    }

    private static function products(): void
    {
        foreach ([
            ['CreateZA Cap', 28000, 'Structured cap with the CreateZA mark embroidered on the front.', 'assets/img/product-cap.svg', 25],
            ['CreateZA T-Shirt', 30000, 'Soft heavyweight cotton tee with a small chest logo.', 'assets/img/product-tee.svg', 40],
            ['CreateZA Hoodie', 65000, 'Midweight hoodie for early call times and late edits.', 'assets/img/product-hoodie.svg', 15],
            ['CreateZA Tote', 25000, 'Canvas tote that fits a camera body, lenses and a laptop.', 'assets/img/product-tote.svg', 30],
        ] as [$name, $price, $description, $image, $stock]) {
            self::insert('products', [
                'name' => $name, 'slug' => slugify($name), 'price_cents' => $price,
                'description' => $description, 'image' => $image, 'stock' => $stock,
            ]);
        }
    }

    private static function analytics(): void
    {
        $platforms = array_keys(Catalog::PLATFORMS);
        $formats = ['Reel', 'Carousel', 'Short', 'Photo', 'Article'];
        for ($m = 5; $m >= 0; $m--) {
            $period = date('Y-m', strtotime("first day of -{$m} months"));
            foreach ($platforms as $p => $platform) {
                // Deterministic placeholder numbers — clearly demo, not real performance.
                $base = (6 - $m) * 10 + $p * 7;
                self::insert('analytics_metrics', [
                    'period' => $period, 'platform' => $platform,
                    'posts_published' => 6 + ($base % 9),
                    'reach' => 1000 + $base * 45,
                    'engagement_rate' => round(2 + (($base * 13) % 40) / 10, 1),
                    'top_format' => $formats[$p],
                ]);
            }
        }
    }
}

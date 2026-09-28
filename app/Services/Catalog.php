<?php
declare(strict_types=1);

namespace App\Services;

/** Fixed option lists shared by forms, validation and views. */
final class Catalog
{
    public const LEAD_STATUSES = [
        'new' => 'New', 'contacted' => 'Contacted', 'discovery' => 'Discovery', 'proposal' => 'Proposal',
        'won' => 'Won', 'onboarding' => 'Onboarding', 'lost' => 'Lost',
    ];

    public const CONTENT_STATUSES = [
        'idea' => 'Idea', 'draft' => 'Draft', 'in_production' => 'In production',
        'awaiting_approval' => 'Awaiting approval', 'approved' => 'Approved',
        'scheduled' => 'Scheduled', 'published' => 'Published',
    ];

    public const PROJECT_STATUSES = [
        'planning' => 'Planning', 'in_progress' => 'In progress', 'in_review' => 'In review',
        'completed' => 'Completed', 'on_hold' => 'On hold',
    ];

    public const PLATFORMS = [
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube',
        'facebook' => 'Facebook', 'linkedin' => 'LinkedIn',
    ];

    public const FORMATS = ['Photo', 'Reel', 'Carousel', 'Story', 'Video', 'Short', 'Graphic', 'Article'];

    public const BUDGETS = ['Under R5 000', 'R5 000 – R15 000', 'R15 000 – R50 000', 'R50 000+', 'Not sure yet'];

    public const GALLERY_CATEGORIES = [
        'photography' => 'Photography', 'video' => 'Video', 'social' => 'Social', 'events' => 'Events',
        'websites' => 'Websites', 'bts' => 'Behind the scenes',
    ];

    public const SERVICES = [
        'photography' => [
            'name' => 'Photography',
            'summary' => 'Striking imagery for people, places, products and stories.',
            'detail' => 'Portraits, brand shoots, products and lifestyle — planned, shot and retouched for every channel you use.',
            'image' => 'assets/media/photography.jpg',
        ],
        'videography' => [
            'name' => 'Videography',
            'summary' => 'Cinematic video for brands, events and digital platforms.',
            'detail' => 'From concept and script to shoot and edit: brand films, reels, interviews and highlight videos.',
            'image' => 'assets/media/video.jpg',
        ],
        'websites' => [
            'name' => 'Websites',
            'summary' => 'Modern, responsive websites built to perform.',
            'detail' => 'Fast, accessible websites that show your work well and turn visitors into enquiries.',
            'image' => 'assets/media/websites.jpg',
        ],
        'social-media' => [
            'name' => 'Social media',
            'summary' => 'Platform-ready content that engages and grows.',
            'detail' => 'Consistent, on-brand posts, reels and stories — produced in batches and scheduled for you.',
            'image' => 'assets/media/social.jpg',
        ],
        'content-strategy' => [
            'name' => 'Content strategy',
            'summary' => 'A clear plan for what to post, where and why.',
            'detail' => 'Audience, pillars, formats and a practical calendar your team can actually keep up with.',
            'image' => 'assets/media/strategy.jpg',
        ],
        'event-coverage' => [
            'name' => 'Event coverage',
            'summary' => 'Photo and video that captures the energy of the day.',
            'detail' => 'Launches, concerts, conferences and community events — with same-day social edits available.',
            'image' => 'assets/media/events.jpg',
        ],
    ];

    public static function serviceName(?string $slug): string
    {
        return self::SERVICES[$slug]['name'] ?? (string) $slug;
    }
}

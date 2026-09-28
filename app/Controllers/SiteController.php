<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Catalog;
use App\Services\LeadService;
use InvalidArgumentException;
use Throwable;

final class SiteController extends Controller
{
    public function home(): void
    {
        $this->view('site/home', [
            'title' => 'Creative work made to move',
            'gallery' => rows('SELECT * FROM assets WHERE public_category IS NOT NULL ORDER BY id DESC LIMIT 9'),
            'work' => rows('SELECT * FROM projects WHERE is_public = 1 ORDER BY due_date DESC LIMIT 3'),
            'products' => rows('SELECT * FROM products WHERE is_active = 1 ORDER BY id LIMIT 4'),
            'events' => rows("SELECT * FROM events WHERE starts_at >= now_local() ORDER BY starts_at LIMIT 3"),
        ]);
    }

    public function about(): void
    {
        $this->view('site/about', ['title' => 'About']);
    }

    public function services(): void
    {
        $this->view('site/services', ['title' => 'Services', 'services' => Catalog::SERVICES]);
    }

    public function gallery(): void
    {
        $category = input('category');
        if (!isset(Catalog::GALLERY_CATEGORIES[$category])) {
            $category = '';
        }
        // Everything is rendered; non-matching items are hidden so JS can filter without reloading.
        $this->view('site/gallery', [
            'title' => 'Gallery',
            'items' => rows('SELECT * FROM assets WHERE public_category IS NOT NULL ORDER BY id DESC'),
            'category' => $category,
        ]);
    }

    public function work(): void
    {
        $this->view('site/work', [
            'title' => 'Work',
            'projects' => rows('SELECT * FROM projects WHERE is_public = 1 ORDER BY due_date DESC'),
        ]);
    }

    public function caseStudy(string $slug): void
    {
        $project = row('SELECT * FROM projects WHERE slug = ? AND is_public = 1', [$slug]);
        if (!$project) {
            $this->notFound();
        }
        $this->view('site/case-study', [
            'title' => $project['name'],
            'project' => $project,
            'gallery' => rows("SELECT * FROM assets WHERE project_id = ? AND type = 'image' ORDER BY id", [$project['id']]),
        ]);
    }

    public function contact(): void
    {
        $this->view('site/contact', ['title' => 'Contact', 'selected' => input('service')]);
    }

    public function submitContact(): void
    {
        // Honeypot: real people never fill this hidden field.
        if (input('website') !== '') {
            redirect('/contact?sent=1');
        }
        try {
            LeadService::create($_POST, 'website');
        } catch (InvalidArgumentException $e) {
            $this->failed($e->getMessage(), '/contact#enquiry');
        }
        flash('success', 'Thanks — your enquiry is in. We’ll be in touch within two working days.');
        redirect('/contact?sent=1');
    }

    public function upload(string $name): void
    {
        if (!\App\Services\Upload::serve($name)) {
            $this->notFound();
        }
    }

    public function health(): void
    {
        $dbOk = true;
        try {
            scalar('SELECT 1');
        } catch (Throwable) {
            $dbOk = false;
        }
        $this->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk ? 'ok' : 'error',
            'time' => date(DATE_ATOM),
        ], $dbOk ? 200 : 503);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\Auth;
use App\Services\Catalog;

final class DashboardController extends Controller
{
    public function overview(): void
    {
        [$scope, $params] = Auth::clientScope();
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +7 days'));

        $count = fn (string $where, array $extra = []) => (int) scalar(
            "SELECT COUNT(*) FROM content_items c LEFT JOIN projects p ON p.id = c.project_id WHERE {$where}{$scope}",
            [...$extra, ...$params]
        );

        $this->admin('overview', [
            'title' => 'Overview',
            'stats' => [
                'drafts' => $count("c.status IN ('idea','draft','in_production')"),
                'awaiting' => $count("c.status = 'awaiting_approval'"),
                'scheduled' => $count("c.publish_at >= ? AND c.publish_at < ? AND c.status IN ('approved','scheduled')", [$weekStart, $weekEnd]),
                'projects' => (int) scalar("SELECT COUNT(*) FROM projects p WHERE p.status IN ('planning','in_progress','in_review'){$scope}", $params),
            ],
            'week' => ContentController::weekGrid($weekStart),
            'weekStart' => $weekStart,
            'attention' => rows(
                "SELECT c.*, (SELECT a.path FROM assets a JOIN content_assets ca ON ca.asset_id = a.id WHERE ca.content_id = c.id LIMIT 1) AS thumb
                 FROM content_items c LEFT JOIN projects p ON p.id = c.project_id
                 WHERE c.status = 'awaiting_approval'{$scope} ORDER BY c.publish_at LIMIT 4",
                $params
            ),
            'projects' => rows(
                "SELECT p.*, cl.name AS client_name FROM projects p LEFT JOIN clients cl ON cl.id = p.client_id
                 WHERE p.status != 'completed'{$scope} ORDER BY p.due_date LIMIT 5",
                $params
            ),
            'assets' => rows(
                "SELECT a.* FROM assets a LEFT JOIN projects p ON p.id = a.project_id WHERE 1=1{$scope} ORDER BY a.created_at DESC LIMIT 6",
                $params
            ),
            'pipeline' => can('admin', 'team')
                ? array_column(rows('SELECT status, COUNT(*) AS n FROM leads GROUP BY status'), 'n', 'status')
                : null,
            'chart' => self::reachSeries(),
            'orders' => can('admin', 'team') ? rows('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5') : [],
            'registrations' => can('admin', 'team')
                ? rows('SELECT r.*, e.title AS event_title FROM event_registrations r JOIN events e ON e.id = r.event_id ORDER BY r.created_at DESC LIMIT 5')
                : [],
        ]);
    }

    /** Reach per platform per month, for the performance chart. */
    public static function reachSeries(): array
    {
        $periods = array_column(rows('SELECT DISTINCT period FROM analytics_metrics ORDER BY period DESC LIMIT 6'), 'period');
        $periods = array_reverse($periods);
        $series = [];
        foreach (Catalog::PLATFORMS as $key => $label) {
            $values = array_column(rows('SELECT period, reach FROM analytics_metrics WHERE platform = ?', [$key]), 'reach', 'period');
            $series[$key] = array_map(fn ($p) => (int) ($values[$p] ?? 0), $periods);
        }
        return ['periods' => $periods, 'series' => $series];
    }

    public function analytics(): void
    {
        $period = input('period', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = date('Y-m');
        }
        $totalProjects = (int) scalar('SELECT COUNT(*) FROM projects');
        $this->admin('analytics', [
            'title' => 'Analytics',
            'period' => $period,
            'metrics' => array_column(rows('SELECT * FROM analytics_metrics WHERE period = ?', [$period]), null, 'platform'),
            'totals' => row('SELECT SUM(posts_published) AS posts, SUM(reach) AS reach, AVG(engagement_rate) AS engagement FROM analytics_metrics WHERE period = ?', [$period]),
            'formats' => rows('SELECT top_format, COUNT(*) AS n FROM analytics_metrics WHERE top_format IS NOT NULL GROUP BY top_format ORDER BY n DESC'),
            'published' => (int) scalar("SELECT COUNT(*) FROM content_items WHERE status = 'published'"),
            'volume' => rows("SELECT substr(publish_at, 1, 7) AS month, COUNT(*) AS n FROM content_items WHERE publish_at IS NOT NULL GROUP BY month ORDER BY month DESC LIMIT 6"),
            'completion' => $totalProjects ? round((int) scalar("SELECT COUNT(*) FROM projects WHERE status = 'completed'") / $totalProjects * 100) : 0,
            'chart' => self::reachSeries(),
        ]);
    }

    public function saveAnalytics(): void
    {
        $period = input('period');
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->failed('Choose a valid month.', '/admin/analytics');
        }
        foreach (Catalog::PLATFORMS as $key => $label) {
            $m = (array) ($_POST['metrics'][$key] ?? []);
            q('INSERT INTO analytics_metrics (period, platform, posts_published, reach, engagement_rate, top_format) VALUES (?, ?, ?, ?, ?, ?)
               ON CONFLICT(period, platform) DO UPDATE SET posts_published = excluded.posts_published, reach = excluded.reach,
               engagement_rate = excluded.engagement_rate, top_format = excluded.top_format', [
                $period, $key,
                max(0, (int) ($m['posts_published'] ?? 0)),
                max(0, (int) ($m['reach'] ?? 0)),
                max(0, min(100, (float) ($m['engagement_rate'] ?? 0))),
                in_array($m['top_format'] ?? '', Catalog::FORMATS, true) ? $m['top_format'] : null,
            ]);
        }
        log_activity('analytics.updated', "Analytics updated for {$period}");
        flash('success', 'Analytics saved for ' . date('F Y', strtotime($period . '-01')) . '.');
        redirect('/admin/analytics?period=' . $period);
    }
}

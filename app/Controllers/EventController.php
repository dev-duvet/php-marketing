<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Validator;

final class EventController extends Controller
{
    private const WITH_COUNTS = 'SELECT e.*, COALESCE((SELECT SUM(guests) FROM event_registrations r WHERE r.event_id = e.id), 0) AS booked FROM events e';

    public function index(): void
    {
        $this->view('events/index', [
            'title' => 'Events',
            'upcoming' => rows(self::WITH_COUNTS . " WHERE starts_at >= now_local() ORDER BY starts_at"),
            'past' => rows(self::WITH_COUNTS . " WHERE starts_at < now_local() ORDER BY starts_at DESC"),
        ]);
    }

    public function show(string $slug): void
    {
        $event = row(self::WITH_COUNTS . ' WHERE slug = ?', [$slug]);
        if (!$event) {
            $this->notFound();
        }
        $this->view('events/show', ['title' => $event['title'], 'event' => $event]);
    }

    public function register(string $slug): void
    {
        $event = row(self::WITH_COUNTS . ' WHERE slug = ?', [$slug]);
        if (!$event) {
            $this->notFound();
        }
        $back = '/events/' . $event['slug'] . '#register';
        if (strtotime($event['starts_at']) < time()) {
            $this->failed('Registration for this event has closed.', $back);
        }
        $v = (new Validator($_POST))
            ->required('name', 'Name')->max('name', 120, 'Name')
            ->required('email', 'Email')->email('email')->max('phone', 40, 'Phone');
        if ($v->fails()) {
            $this->failed($v->firstError(), $back);
        }
        $guests = max(1, min(5, (int) input('guests', '1')));
        if ((int) $event['booked'] + $guests > (int) $event['capacity']) {
            $this->failed('Sorry, there are not enough spaces left for that booking.', $back);
        }
        if (row('SELECT id FROM event_registrations WHERE event_id = ? AND LOWER(email) = ?', [$event['id'], mb_strtolower(input('email'))])) {
            $this->failed('That email address is already registered for this event.', $back);
        }
        q('INSERT INTO event_registrations (event_id, name, email, phone, guests) VALUES (?, ?, ?, ?, ?)', [
            $event['id'], input('name'), input('email'), input('phone'), $guests,
        ]);
        log_activity('event.registration', "Registration for {$event['title']}");
        flash('success', 'You’re registered! We’ll email the details closer to the day.');
        redirect($back);
    }
}

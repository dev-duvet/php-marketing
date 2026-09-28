<?php
declare(strict_types=1);

use App\Services\LeadService;

$enquiry = [
    'name' => 'Test Person', 'email' => 'test@example.com', 'phone' => '+27 11 000 0000',
    'organisation' => 'Test Org', 'service' => 'videography', 'budget' => 'R5 000 – R15 000',
    'details' => 'We need a launch video.', 'preferred_start' => '2030-01-15',
];

test('a contact form enquiry creates a new lead with an activity entry', function () use ($enquiry) {
    $id = LeadService::create($enquiry, 'website');
    $lead = row('SELECT * FROM leads WHERE id = ?', [$id]);
    assert_same('new', $lead['status']);
    assert_same('website', $lead['source']);
    assert_same('videography', $lead['service']);
    assert_same(1, (int) scalar('SELECT COUNT(*) FROM lead_activities WHERE lead_id = ?', [$id]));
});

test('enquiries without required fields are rejected', function () use ($enquiry) {
    assert_throws(fn () => LeadService::create(['name' => '', 'email' => 'x@example.com'] + $enquiry));
    assert_throws(fn () => LeadService::create(['email' => 'not-an-email'] + $enquiry));
    assert_throws(fn () => LeadService::create(['service' => 'unknown'] + $enquiry));
    assert_throws(fn () => LeadService::create(['details' => ''] + $enquiry));
});

test('moving a lead logs the stage change', function () use ($enquiry) {
    $id = LeadService::create($enquiry);
    LeadService::moveTo($id, 'proposal');
    assert_same('proposal', scalar('SELECT status FROM leads WHERE id = ?', [$id]));
    assert_same(2, (int) scalar('SELECT COUNT(*) FROM lead_activities WHERE lead_id = ?', [$id]));
    assert_throws(fn () => LeadService::moveTo($id, 'bogus'));
});

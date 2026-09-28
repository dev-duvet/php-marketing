<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class LeadService
{
    public const FIELDS = ['name', 'email', 'phone', 'organisation', 'service', 'budget', 'details', 'preferred_start'];

    public static function validate(array $data): Validator
    {
        return (new Validator($data))
            ->required('name', 'Name')->max('name', 120, 'Name')
            ->required('email', 'Email')->email('email')
            ->max('phone', 40, 'Phone')
            ->max('organisation', 160, 'Business or organisation')
            ->required('service', 'Service')->in('service', array_keys(Catalog::SERVICES), 'service')
            ->in('budget', Catalog::BUDGETS, 'budget range')
            ->required('details', 'Project details')->max('details', 5000, 'Project details')
            ->date('preferred_start');
    }

    /** Create a lead from the public contact form (or the dashboard). Returns the new lead id. */
    public static function create(array $data, string $source = 'website', ?int $userId = null): int
    {
        $validator = self::validate($data);
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->firstError());
        }
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $values[] = $value === '' ? null : $value;
        }
        q('INSERT INTO leads (name, email, phone, organisation, service, budget, details, preferred_start, source, status)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [...$values, $source, 'new']);
        $id = (int) db()->lastInsertId();

        self::addActivity($id, $source === 'website' ? 'Enquiry received via the website contact form.' : 'Lead created in the dashboard.', $userId);
        log_activity('lead.created', "Lead #{$id} created ({$data['name']})");
        return $id;
    }

    public static function moveTo(int $id, string $status, ?int $userId = null): void
    {
        if (!isset(Catalog::LEAD_STATUSES[$status])) {
            throw new InvalidArgumentException('Unknown lead status.');
        }
        $lead = row('SELECT status FROM leads WHERE id = ?', [$id]);
        if ($lead === null) {
            throw new InvalidArgumentException('Lead not found.');
        }
        if ($lead['status'] === $status) {
            return;
        }
        q('UPDATE leads SET status = ?, updated_at = now_local() WHERE id = ?', [$status, $id]);
        self::addActivity($id, 'Moved from ' . Catalog::LEAD_STATUSES[$lead['status']] . ' to ' . Catalog::LEAD_STATUSES[$status] . '.', $userId);
    }

    public static function addActivity(int $leadId, string $body, ?int $userId = null): void
    {
        q('INSERT INTO lead_activities (lead_id, user_id, body) VALUES (?, ?, ?)', [$leadId, $userId, $body]);
    }
}

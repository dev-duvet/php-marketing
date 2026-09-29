import { logActivity } from './app.ts';
import { BUDGETS, LEAD_STATUSES, SERVICES } from './catalog.ts';
import { insert, one, run } from './db.ts';
import { ValidationError, Validator, str, type Input } from './validate.ts';

const FIELDS = ['name', 'email', 'phone', 'organisation', 'service', 'budget', 'details', 'preferred_start'] as const;

export function validateLead(data: Input): Validator {
  return new Validator(data)
    .required('name', 'Name').max('name', 120, 'Name')
    .required('email', 'Email').email('email')
    .max('phone', 40, 'Phone')
    .max('organisation', 160, 'Business or organisation')
    .required('service', 'Service').in('service', Object.keys(SERVICES), 'service')
    .in('budget', BUDGETS, 'budget range')
    .required('details', 'Project details').max('details', 5000, 'Project details')
    .date('preferred_start');
}

/** Create a lead from the public contact form or the dashboard. */
export async function createLead(data: Input, source = 'website', userId: number | null = null): Promise<number> {
  validateLead(data).check();
  const row: Record<string, string | null> = {};
  for (const f of FIELDS) row[f] = str(data[f]) || null;
  const id = await insert('leads', { ...row, source, status: 'new' });
  await addLeadActivity(id, source === 'website' ? 'Enquiry received via the website contact form.' : 'Lead created in the dashboard.', userId);
  await logActivity(userId, 'lead.created', `Lead #${id} created (${row.name})`);
  return id;
}

export async function moveLead(id: number, status: string, userId: number | null = null): Promise<void> {
  if (!LEAD_STATUSES[status]) throw new ValidationError('Unknown lead status.');
  const lead = await one('SELECT status FROM leads WHERE id = ?', [id]);
  if (!lead) throw new ValidationError('Lead not found.');
  if (lead.status === status) return;
  await run('UPDATE leads SET status = ?, updated_at = now_local() WHERE id = ?', [status, id]);
  await addLeadActivity(id, `Moved from ${LEAD_STATUSES[lead.status]} to ${LEAD_STATUSES[status]}.`, userId);
}

export async function addLeadActivity(leadId: number, body: string, userId: number | null = null): Promise<void> {
  await insert('lead_activities', { lead_id: leadId, user_id: userId, body });
}

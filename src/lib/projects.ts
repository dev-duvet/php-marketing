import { logActivity, uniqueSlug } from './app.ts';
import { PROJECT_STATUSES } from './catalog.ts';
import { insert, one, run, transaction } from './db.ts';
import { ValidationError, Validator, arr, int, str, type Input } from './validate.ts';

export function validateProject(data: Input): Validator {
  return new Validator(data)
    .required('name', 'Project name').max('name', 160, 'Project name')
    .in('status', Object.keys(PROJECT_STATUSES), 'status')
    .date('start_date').date('due_date');
}

export async function saveProject(data: Input, id: number | null = null, userId: number | null = null): Promise<number> {
  validateProject(data).check();
  return transaction(async () => {
    const name = str(data.name);
    let clientId: number | null = str(data.client_id) ? int(data.client_id) : null;
    const newClient = str(data.new_client);
    if (clientId === null && newClient) clientId = await insert('clients', { name: newClient });

    const fields = {
      name,
      client_id: clientId,
      description: str(data.description),
      service_type: str(data.service_type),
      start_date: str(data.start_date) || null,
      due_date: str(data.due_date) || null,
      status: str(data.status) || 'planning',
      deliverables: str(data.deliverables),
      progress: Math.max(0, Math.min(100, int(data.progress))),
      notes: str(data.notes),
      is_public: data.is_public ? 1 : 0,
      outcomes: str(data.outcomes),
    };

    let projectId = id;
    if (projectId === null) {
      projectId = await insert('projects', { ...fields, slug: await uniqueSlug('projects', name) });
      await logActivity(userId, 'project.created', `Project #${projectId} created (${name})`);
    } else {
      if (!(await one('SELECT id FROM projects WHERE id = ?', [projectId]))) throw new ValidationError('Project not found.');
      await run(
        'UPDATE projects SET name = ?, client_id = ?, description = ?, service_type = ?, start_date = ?, due_date = ?, status = ?, deliverables = ?, progress = ?, notes = ?, is_public = ?, outcomes = ?, updated_at = now_local() WHERE id = ?',
        [...Object.values(fields), projectId],
      );
      await logActivity(userId, 'project.updated', `Project #${projectId} updated`);
    }

    if ('members' in data) {
      await run('DELETE FROM project_members WHERE project_id = ?', [projectId]);
      for (const member of arr(data.members)) {
        if (await one("SELECT id FROM users WHERE id = ? AND role != 'client'", [int(member)])) {
          await run('INSERT INTO project_members (project_id, user_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [projectId, int(member)]);
        }
      }
    }
    return projectId;
  });
}

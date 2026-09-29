import { logActivity } from './app.ts';
import { CONTENT_STATUSES, FORMATS, PLATFORMS } from './catalog.ts';
import { all, count, insert, one, run, transaction } from './db.ts';
import { parseDate, toDateTimeString } from './format.ts';
import { ValidationError, Validator, arr, int, str, type Input } from './validate.ts';

export function validateContent(data: Input): Validator {
  return new Validator(data)
    .required('title', 'Title').max('title', 160, 'Title')
    .max('caption', 2200, 'Caption')
    .in('status', Object.keys(CONTENT_STATUSES), 'status')
    .in('format', FORMATS, 'format')
    .date('publish_at');
}

/** Create or update a content item; returns its id. */
export async function saveContent(data: Input, id: number | null, userId: number): Promise<number> {
  validateContent(data).check();
  const platforms = arr(data.platforms).filter((p) => PLATFORMS[p]);
  if (!platforms.length) throw new ValidationError('Choose at least one platform.');

  const publish = parseDate(str(data.publish_at));
  const fields = {
    title: str(data.title),
    project_id: str(data.project_id) ? int(data.project_id) : null,
    format: str(data.format) || 'Photo',
    caption: str(data.caption),
    publish_at: publish ? toDateTimeString(publish).slice(0, 16) : null,
    assigned_to: str(data.assigned_to) ? int(data.assigned_to) : null,
    internal_notes: str(data.internal_notes),
    // Entering approval always goes through submitContent() so it is versioned and logged.
    status: str(data.status) === 'awaiting_approval' || !str(data.status) ? 'draft' : str(data.status),
  };

  return transaction(async () => {
    let contentId = id;
    if (contentId === null) {
      contentId = await insert('content_items', { ...fields, created_by: userId });
      await logActivity(userId, 'content.created', `Content #${contentId} created`);
    } else {
      await run(
        'UPDATE content_items SET title = ?, project_id = ?, format = ?, caption = ?, publish_at = ?, assigned_to = ?, internal_notes = ?, status = ?, updated_at = now_local() WHERE id = ?',
        [...Object.values(fields), contentId],
      );
    }
    await run('DELETE FROM content_platforms WHERE content_id = ?', [contentId]);
    for (const p of platforms) await run('INSERT INTO content_platforms (content_id, platform) VALUES (?, ?)', [contentId, p]);
    for (const assetId of arr(data.asset_ids)) await attachAsset(contentId, int(assetId));
    return contentId;
  });
}

export async function attachAsset(contentId: number, assetId: number): Promise<void> {
  if (assetId > 0 && (await one('SELECT id FROM assets WHERE id = ?', [assetId]))) {
    await run('INSERT INTO content_assets (content_id, asset_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [contentId, assetId]);
  }
}

/** Send for approval: bumps the version after the first round and stores a snapshot. */
export async function submitContent(id: number, userId: number): Promise<void> {
  const item = await one('SELECT * FROM content_items WHERE id = ?', [id]);
  if (!item) throw new ValidationError('Content not found.');
  const hadRound = (await count("SELECT COUNT(*) FROM content_approvals WHERE content_id = ? AND action = 'submitted'", [id])) > 0;
  const version = hadRound ? Number(item.version) + 1 : Number(item.version);
  const assets = await all('SELECT a.title FROM assets a JOIN content_assets ca ON ca.asset_id = a.id WHERE ca.content_id = ?', [id]);
  const snapshot = JSON.stringify({ title: item.title, caption: item.caption, assets: assets.map((a) => a.title) });
  await run("UPDATE content_items SET status = 'awaiting_approval', version = ?, updated_at = now_local() WHERE id = ?", [version, id]);
  await insert('content_approvals', { content_id: id, user_id: userId, action: 'submitted', version, snapshot });
  await logActivity(userId, 'content.submitted', `Content #${id} sent for approval (v${version})`);
}

export async function reviewContent(id: number, decision: string, userId: number, note = ''): Promise<void> {
  const item = await one('SELECT * FROM content_items WHERE id = ?', [id]);
  if (!item) throw new ValidationError('Content not found.');
  if (item.status !== 'awaiting_approval') throw new ValidationError('Only items awaiting approval can be reviewed.');
  if (decision !== 'approved' && decision !== 'changes_requested') throw new ValidationError('Unknown decision.');
  const text = note.trim();
  if (decision === 'changes_requested' && !text) throw new ValidationError('Tell the team what needs to change.');
  await run('UPDATE content_items SET status = ?, updated_at = now_local() WHERE id = ?', [decision === 'approved' ? 'approved' : 'in_production', id]);
  await insert('content_approvals', { content_id: id, user_id: userId, action: decision, version: item.version, note: text || null });
  if (text) await commentOnContent(id, userId, text);
  await logActivity(userId, `content.${decision}`, `Content #${id} ${decision.replace('_', ' ')}`);
}

export async function commentOnContent(id: number, userId: number, body: string): Promise<void> {
  const text = body.trim();
  if (!text || text.length > 2000) throw new ValidationError('Comments must be between 1 and 2000 characters.');
  const version = Number((await one('SELECT version FROM content_items WHERE id = ?', [id]))?.version ?? 1);
  await insert('content_comments', { content_id: id, user_id: userId, version, body: text });
}

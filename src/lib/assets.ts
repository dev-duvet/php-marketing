import { one, run } from './db.ts';

/** Replace (or add to) an asset's comma-separated tags. */
export async function syncTags(assetId: number, tags: string, replace = false): Promise<void> {
  if (replace) await run('DELETE FROM asset_tag_map WHERE asset_id = ?', [assetId]);
  const names = tags.split(',').map((t) => t.trim().toLowerCase().slice(0, 40)).filter(Boolean);
  for (const name of new Set(names)) {
    await run('INSERT INTO asset_tags (name) VALUES (?) ON CONFLICT DO NOTHING', [name]);
    const tag = await one('SELECT id FROM asset_tags WHERE name = ?', [name]);
    await run('INSERT INTO asset_tag_map (asset_id, tag_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [assetId, tag!.id]);
  }
}

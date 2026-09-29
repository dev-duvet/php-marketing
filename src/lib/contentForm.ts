import type { AstroGlobal } from 'astro';
import { saveContent, submitContent } from './content.ts';
import { done, handle, readForm } from './forms.ts';
import { createAsset, filesFrom } from './upload.ts';
import { int, str } from './validate.ts';

/** Shared POST handler for the content editor (create and edit). */
export async function handleContentPost(Astro: AstroGlobal, id: number | null): Promise<Response> {
  const { form, data } = await readForm(Astro);
  const back = id ? `/admin/content/${id}/edit` : '/admin/content/new';
  const userId = Astro.locals.user!.id;
  return handle(Astro, data, back, async () => {
    // Uploaded files become library assets attached to this item.
    const assetIds = [...(Array.isArray(data.asset_ids) ? data.asset_ids : data.asset_ids ? [data.asset_ids] : [])];
    for (const file of filesFrom(form, 'uploads')) {
      assetIds.push(String(await createAsset(file, str(data.project_id) ? int(data.project_id) : null, userId)));
    }
    const contentId = await saveContent({ ...data, asset_ids: assetIds }, id, userId);
    if (data.action === 'submit') {
      await submitContent(contentId, userId);
      return done(Astro, 'Sent for approval.', `/admin/approvals/${contentId}`);
    }
    return done(Astro, 'Content saved.', `/admin/content/${contentId}/edit`);
  });
}

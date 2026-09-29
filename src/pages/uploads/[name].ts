import type { APIRoute } from 'astro';
import { readStoredFile } from '../../lib/upload.ts';

export const GET: APIRoute = async ({ params }) => {
  const file = await readStoredFile(params.name ?? '');
  if (!file) return new Response('Not found', { status: 404 });
  return new Response(new Uint8Array(file.body), {
    headers: {
      'Content-Type': file.mime,
      'Content-Length': String(file.body.length),
      'Content-Disposition': `inline; filename="${params.name}"`,
      'Cache-Control': 'public, max-age=31536000, immutable',
    },
  });
};

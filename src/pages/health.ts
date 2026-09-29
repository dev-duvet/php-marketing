import type { APIRoute } from 'astro';
import { driver, val } from '../lib/db.ts';

export const GET: APIRoute = async () => {
  let ok = true;
  try {
    await val('SELECT 1');
  } catch {
    ok = false;
  }
  return Response.json(
    { status: ok ? 'ok' : 'degraded', database: ok ? 'ok' : 'error', driver: driver(), time: new Date().toISOString() },
    { status: ok ? 200 : 503 },
  );
};

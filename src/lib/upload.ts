import { randomBytes } from 'node:crypto';
import { insert, one, run } from './db.ts';
import { env } from './env.ts';
import { ValidationError } from './validate.ts';

export type AssetType = 'image' | 'video' | 'design' | 'document';

interface Kind {
  mime: string;
  ext: string;
  type: AssetType;
}

/** Identify a file from its leading bytes (never trust the browser's MIME type or file name). */
export function sniff(bytes: Uint8Array): Kind | null {
  const b = (i: number) => bytes[i];
  const ascii = (start: number, len: number) => String.fromCharCode(...bytes.slice(start, start + len));
  if (b(0) === 0xff && b(1) === 0xd8 && b(2) === 0xff) return { mime: 'image/jpeg', ext: 'jpg', type: 'image' };
  if (ascii(0, 8) === '\x89PNG\r\n\x1a\n') return { mime: 'image/png', ext: 'png', type: 'image' };
  if (ascii(0, 6) === 'GIF87a' || ascii(0, 6) === 'GIF89a') return { mime: 'image/gif', ext: 'gif', type: 'image' };
  if (ascii(0, 4) === 'RIFF' && ascii(8, 4) === 'WEBP') return { mime: 'image/webp', ext: 'webp', type: 'image' };
  if (ascii(4, 4) === 'ftyp') {
    const brand = ascii(8, 4);
    return brand === 'qt  ' ? { mime: 'video/quicktime', ext: 'mov', type: 'video' } : { mime: 'video/mp4', ext: 'mp4', type: 'video' };
  }
  if (b(0) === 0x1a && b(1) === 0x45 && b(2) === 0xdf && b(3) === 0xa3) return { mime: 'video/webm', ext: 'webm', type: 'video' };
  if (ascii(0, 5) === '%PDF-') return { mime: 'application/pdf', ext: 'pdf', type: 'document' };
  if (ascii(0, 4) === '8BPS') return { mime: 'image/vnd.adobe.photoshop', ext: 'psd', type: 'design' };
  if (ascii(0, 4) === '%!PS') return { mime: 'application/postscript', ext: 'ai', type: 'design' };
  return null;
}

export const ALLOWED_MIMES = new Set([
  'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm',
  'application/pdf', 'image/vnd.adobe.photoshop', 'application/postscript',
]);

export function maxUploadMb(): number {
  return Number(env('UPLOAD_MAX_MB', process.env.VERCEL ? '4' : '25'));
}

export interface StoredFile {
  path: string;
  mime: string;
  type: AssetType;
  size: number;
}

/** Validate and store an uploaded file in the database (works on serverless hosts with no disk). */
export async function storeUpload(file: File, onlyTypes: AssetType[] = []): Promise<StoredFile> {
  if (!file.size) throw new ValidationError('The upload was empty.');
  if (file.size > maxUploadMb() * 1024 * 1024) throw new ValidationError(`Files must be smaller than ${maxUploadMb()}MB.`);
  const bytes = new Uint8Array(await file.arrayBuffer());
  const kind = sniff(bytes);
  if (!kind) throw new ValidationError('That file type is not allowed. Use JPG, PNG, WEBP, GIF, MP4, WEBM, MOV, PDF, PSD or AI.');
  if (onlyTypes.length && !onlyTypes.includes(kind.type)) throw new ValidationError('That file type is not allowed here.');
  const name = `${randomBytes(16).toString('hex')}.${kind.ext}`;
  await run('INSERT INTO stored_files (name, mime, size, data) VALUES (?, ?, ?, ?)', [name, kind.mime, bytes.length, Buffer.from(bytes).toString('base64')]);
  return { path: `uploads/${name}`, mime: kind.mime, type: kind.type, size: bytes.length };
}

export async function createAsset(file: File, projectId: number | null, userId: number | null): Promise<number> {
  const stored = await storeUpload(file);
  const title = file.name.replace(/\.[^.]+$/, '').slice(0, 120) || 'Untitled';
  return insert('assets', { title, path: stored.path, mime: stored.mime, type: stored.type, size: stored.size, project_id: projectId, uploaded_by: userId });
}

export async function deleteStoredFile(path: string): Promise<void> {
  if (path.startsWith('uploads/')) await run('DELETE FROM stored_files WHERE name = ?', [path.slice('uploads/'.length)]);
}

export async function readStoredFile(name: string): Promise<{ mime: string; body: Buffer } | null> {
  if (!/^[a-f0-9]{32}\.[a-z0-9]{2,4}$/.test(name)) return null;
  const row = await one('SELECT mime, data FROM stored_files WHERE name = ?', [name]);
  if (!row || !ALLOWED_MIMES.has(row.mime)) return null;
  return { mime: row.mime, body: Buffer.from(row.data, 'base64') };
}

/** Pull all File entries for a field (single or multiple input). */
export function filesFrom(form: FormData, field: string): File[] {
  return form.getAll(field).filter((f): f is File => typeof f !== 'string' && f.size > 0);
}

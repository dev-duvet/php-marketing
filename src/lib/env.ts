import { existsSync } from 'node:fs';

// Local development: load .env into process.env (Vercel injects real env vars at runtime).
if (existsSync('.env') && typeof process.loadEnvFile === 'function' && !process.env.VERCEL) {
  process.loadEnvFile('.env');
}

// All stored timestamps are wall-clock time in the business's timezone.
process.env.TZ = process.env.APP_TIMEZONE || 'Africa/Johannesburg';

export function env(key: string, fallback = ''): string {
  const value = process.env[key];
  return value === undefined || value === '' ? fallback : value;
}

export const isProduction = () => env('NODE_ENV') === 'production' || Boolean(process.env.VERCEL);

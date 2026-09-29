/**
 * Drop and rebuild the database with demo data.
 *   npm run db:fresh                 (local SQLite, or DATABASE_URL if set in .env)
 * Destructive: every table is dropped. Uses DATABASE_URL/POSTGRES_URL when present.
 */
import '../src/lib/env.ts';

process.env.DB_AUTO_SETUP = 'true';
const { closeDatabase, count, driver, ensureReady, resetDatabase } = await import('../src/lib/db.ts');

if (driver() === 'pgsql' && !process.argv.includes('--yes')) {
  console.error('Refusing to wipe a PostgreSQL database without --yes (npm run db:fresh -- --yes).');
  process.exit(1);
}
// Connect first (builds the schema if missing), then wipe and rebuild.
await ensureReady();
await resetDatabase();
await ensureReady();
console.log(`Rebuilt ${driver()} database: ${await count('SELECT COUNT(*) FROM users')} users, ${await count('SELECT COUNT(*) FROM content_items')} content items.`);
await closeDatabase();

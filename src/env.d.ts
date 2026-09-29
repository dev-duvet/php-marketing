declare namespace App {
  interface Locals {
    session: import('./lib/session.ts').Session;
    user: import('./lib/auth.ts').User | null;
    settings: Record<string, string>;
    ip: string;
  }
}

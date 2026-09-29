const escapeHtml = (s: string) => s.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

/** A minimal, self-contained error page (used for 403/419 without re-entering the router). */
export function plainPage(status: number, title: string, message: string, href = '/', linkText = 'Back to home'): Response {
  const html = `<!doctype html><html lang="en-ZA"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>${escapeHtml(title)} — CreateZA</title><link rel="stylesheet" href="/assets/css/app.css"></head><body><main class="section container narrow center"><p class="eyebrow">${status}</p><h1>${escapeHtml(title)}</h1><p class="lead muted">${escapeHtml(message)}</p><p><a class="btn btn--primary" href="${href}">${escapeHtml(linkText)}</a></p></main></body></html>`;
  return new Response(html, { status, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

export const forbidden = () => plainPage(403, 'You don’t have access to this page.', 'Ask an admin if you think you should.', '/admin', 'Back to overview');

export function wantsJson(request: Request): boolean {
  return (request.headers.get('accept') ?? '').includes('application/json');
}

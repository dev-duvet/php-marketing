/** Formatting helpers. Stored dates are local wall-clock strings: 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM[:SS]'. */

const pad = (n: number) => String(n).padStart(2, '0');
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const DAYS_LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

export function toDateString(d: Date): string {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function toDateTimeString(d: Date): string {
  return `${toDateString(d)} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

export function nowLocal(): string {
  return toDateTimeString(new Date());
}

export function today(): string {
  return toDateString(new Date());
}

/** Parse a stored date string as local time. Returns null when empty/invalid. */
export function parseDate(value: string | null | undefined): Date | null {
  if (!value) return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/.exec(value);
  if (!m) return null;
  const d = new Date(+m[1], +m[2] - 1, +m[3], +(m[4] ?? 0), +(m[5] ?? 0), +(m[6] ?? 0));
  return Number.isNaN(d.getTime()) ? null : d;
}

export function isValidDate(value: string): boolean {
  return parseDate(value) !== null;
}

export function addDays(date: string, days: number): string {
  const d = parseDate(date)!;
  d.setDate(d.getDate() + days);
  return toDateString(d);
}

export function mondayOf(date: string = today()): string {
  const d = parseDate(date) ?? new Date();
  const offset = (d.getDay() + 6) % 7;
  d.setDate(d.getDate() - offset);
  return toDateString(d);
}

type Style = 'date' | 'short' | 'dateShort' | 'shortTime' | 'full' | 'dayName' | 'dayNum' | 'monthUpper' | 'month' | 'monthYear' | 'monthLongYear' | 'time' | 'dateTime';

export function fmtDate(value: string | null | undefined, style: Style = 'date'): string {
  const d = parseDate(value);
  if (!d) return '—';
  const [y, mo, day, h, mi] = [d.getFullYear(), d.getMonth(), d.getDate(), d.getHours(), d.getMinutes()];
  switch (style) {
    case 'date': return `${day} ${MONTHS[mo]} ${y}`;
    case 'short': return `${MONTHS[mo]} ${day}`;
    case 'dateShort': return `${MONTHS[mo]} ${day}, ${y}`;
    case 'shortTime': return `${MONTHS[mo]} ${day}, ${pad(h)}:${pad(mi)}`;
    case 'dateTime': return `${day} ${MONTHS[mo]} ${y}, ${pad(h)}:${pad(mi)}`;
    case 'full': return `${DAYS_LONG[d.getDay()]}, ${day} ${MONTHS_LONG[mo]} ${y} · ${pad(h)}:${pad(mi)}`;
    case 'dayName': return DAYS[d.getDay()];
    case 'dayNum': return pad(day);
    case 'monthUpper': return MONTHS[mo].toUpperCase();
    case 'month': return MONTHS[mo];
    case 'monthYear': return `${MONTHS[mo]} ${y}`;
    case 'monthLongYear': return `${MONTHS_LONG[mo]} ${y}`;
    case 'time': return `${pad(h)}:${pad(mi)}`;
  }
}

export function timeAgo(value: string): string {
  const d = parseDate(value);
  if (!d) return '';
  const diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60) return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
  if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;
  return fmtDate(value);
}

export function money(cents: number): string {
  const rands = cents / 100;
  const formatted = rands.toFixed(cents % 100 === 0 ? 0 : 2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  return `R${formatted}`;
}

export function humanSize(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let i = 0;
  while (size >= 1024 && i < units.length - 1) {
    size /= 1024;
    i++;
  }
  return `${Math.round(size * 10) / 10} ${units[i]}`;
}

export function slugify(text: string): string {
  const slug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  return slug || Math.random().toString(16).slice(2, 10);
}

export function mediaUrl(path: string | null | undefined): string {
  if (!path) return '/assets/img/placeholder.svg';
  return path.startsWith('http') ? path : `/${path.replace(/^\//, '')}`;
}

export function extension(path: string): string {
  return (path.split('.').pop() ?? '').toUpperCase();
}

export function lines(text: string | null | undefined): string[] {
  return (text ?? '').split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
}

export function initials(name: string): string {
  return name.split(/\s+/).slice(0, 2).map((w) => w[0] ?? '').join('').toUpperCase();
}

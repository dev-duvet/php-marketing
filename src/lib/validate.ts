import { isValidDate } from './format.ts';

export type Input = Record<string, any>;

export class ValidationError extends Error {}

/** Small fluent validator; throws ValidationError with the first message via check(). */
export class Validator {
  private errors: string[] = [];
  private data: Input;

  constructor(data: Input) {
    this.data = data;
  }

  private str(field: string): string {
    const v = this.data[field];
    return typeof v === 'string' ? v.trim() : v == null ? '' : String(v);
  }

  required(field: string, label: string): this {
    if (this.str(field) === '') this.errors.push(`${label} is required.`);
    return this;
  }

  email(field: string): this {
    const v = this.str(field);
    if (v !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) this.errors.push('Enter a valid email address.');
    return this;
  }

  max(field: string, length: number, label: string): this {
    if ([...this.str(field)].length > length) this.errors.push(`${label} must be ${length} characters or fewer.`);
    return this;
  }

  in(field: string, allowed: string[], label: string): this {
    const v = this.str(field);
    if (v !== '' && !allowed.includes(v)) this.errors.push(`Choose a valid ${label}.`);
    return this;
  }

  date(field: string): this {
    const v = this.str(field);
    if (v !== '' && !isValidDate(v)) this.errors.push('Enter a valid date.');
    return this;
  }

  fails(): boolean {
    return this.errors.length > 0;
  }

  first(): string {
    return this.errors[0] ?? '';
  }

  check(): void {
    if (this.fails()) throw new ValidationError(this.first());
  }
}

/** Convert FormData to a plain object; repeated names or names ending in [] become arrays. */
export function formToObject(form: FormData): Input {
  const out: Input = {};
  for (const [rawKey, value] of form.entries()) {
    if (typeof value !== 'string') continue;
    const key = rawKey.endsWith('[]') ? rawKey.slice(0, -2) : rawKey;
    if (rawKey.endsWith('[]')) {
      (out[key] ??= []).push(value);
    } else if (key in out) {
      out[key] = Array.isArray(out[key]) ? [...out[key], value] : [out[key], value];
    } else {
      out[key] = value;
    }
  }
  return out;
}

export function str(value: unknown): string {
  return typeof value === 'string' ? value.trim() : '';
}

export function int(value: unknown, fallback = 0): number {
  const n = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(n) ? n : fallback;
}

export function arr(value: unknown): string[] {
  if (Array.isArray(value)) return value.map(String);
  return value == null || value === '' ? [] : [String(value)];
}

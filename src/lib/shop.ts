import { randomBytes } from 'node:crypto';
import { logActivity } from './app.ts';
import { all, count, insert, one, run, transaction } from './db.ts';
import { nowLocal } from './format.ts';
import { ValidationError, Validator, int, str, type Input } from './validate.ts';

export interface CartLine {
  product: Record<string, any>;
  quantity: number;
  line: number;
}

export interface CartSummary {
  items: CartLine[];
  subtotal: number;
  count: number;
}

export async function cartSummary(cart: Record<string, number> = {}): Promise<CartSummary> {
  const ids = Object.keys(cart).map(Number).filter((n) => n > 0);
  const items: CartLine[] = [];
  if (ids.length) {
    const products = await all(`SELECT * FROM products WHERE is_active = 1 AND id IN (${ids.map(() => '?').join(',')}) ORDER BY id`, ids);
    for (const product of products) {
      const quantity = Math.min(Number(cart[product.id]), Math.max(0, Number(product.stock)));
      if (quantity < 1) continue;
      items.push({ product, quantity, line: quantity * Number(product.price_cents) });
    }
  }
  return {
    items,
    subtotal: items.reduce((s, i) => s + i.line, 0),
    count: items.reduce((s, i) => s + i.quantity, 0),
  };
}

export async function addToCart(cart: Record<string, number>, productId: number, qty: number): Promise<string> {
  const product = await one('SELECT * FROM products WHERE id = ? AND is_active = 1', [productId]);
  if (!product) throw new ValidationError('That product is not available.');
  const current = cart[productId] ?? 0;
  cart[productId] = Math.min(current + Math.max(1, Math.min(10, qty)), Number(product.stock), 10);
  return product.name;
}

export async function placeOrder(data: Input, cart: Record<string, number>): Promise<string> {
  const summary = await cartSummary(cart);
  if (!summary.count) throw new ValidationError('Your cart is empty.');
  new Validator(data)
    .required('customer_name', 'Name').max('customer_name', 120, 'Name')
    .required('email', 'Email').email('email')
    .required('address', 'Address').max('address', 255, 'Address')
    .required('city', 'City')
    .required('postal_code', 'Postal code').max('postal_code', 12, 'Postal code')
    .check();

  const reference = `CZA-${randomBytes(4).toString('hex').toUpperCase()}`;
  await transaction(async () => {
    const orderId = await insert('orders', {
      reference, customer_name: str(data.customer_name), email: str(data.email), phone: str(data.phone),
      address: str(data.address), city: str(data.city), postal_code: str(data.postal_code), subtotal_cents: summary.subtotal,
    });
    for (const item of summary.items) {
      await insert('order_items', {
        order_id: orderId, product_id: item.product.id, product_name: item.product.name,
        unit_price_cents: item.product.price_cents, quantity: item.quantity, line_total_cents: item.line,
      });
      await run('UPDATE products SET stock = stock - ? WHERE id = ?', [item.quantity, item.product.id]);
    }
  });
  await logActivity(null, 'order.created', `Order ${reference} placed`);
  return reference;
}

export const EVENT_WITH_COUNTS =
  'SELECT e.*, COALESCE((SELECT SUM(guests) FROM event_registrations r WHERE r.event_id = e.id), 0) AS booked FROM events e';

export async function registerForEvent(slug: string, data: Input): Promise<void> {
  const event = await one(`${EVENT_WITH_COUNTS} WHERE slug = ?`, [slug]);
  if (!event) throw new ValidationError('Event not found.');
  if (event.starts_at < nowLocal()) throw new ValidationError('Registration for this event has closed.');
  new Validator(data).required('name', 'Name').max('name', 120, 'Name').required('email', 'Email').email('email').max('phone', 40, 'Phone').check();
  const guests = Math.max(1, Math.min(5, int(data.guests, 1)));
  if (Number(event.booked) + guests > Number(event.capacity)) throw new ValidationError('Sorry, there are not enough spaces left for that booking.');
  const email = str(data.email).toLowerCase();
  if ((await count('SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND LOWER(email) = ?', [event.id, email])) > 0) {
    throw new ValidationError('That email address is already registered for this event.');
  }
  await insert('event_registrations', { event_id: event.id, name: str(data.name), email, phone: str(data.phone), guests });
  await logActivity(null, 'event.registration', `Registration for ${event.title}`);
}

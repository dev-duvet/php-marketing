<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Validator;

final class StoreController extends Controller
{
    public function index(): void
    {
        $this->view('store/index', [
            'title' => 'Store',
            'products' => rows('SELECT * FROM products WHERE is_active = 1 ORDER BY id'),
        ]);
    }

    public function show(string $slug): void
    {
        $product = row('SELECT * FROM products WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$product) {
            $this->notFound();
        }
        $this->view('store/show', ['title' => $product['name'], 'product' => $product]);
    }

    /** @return array{items: array, subtotal: int, count: int} */
    public static function cartSummary(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $subtotal = 0;
        $count = 0;
        if ($cart) {
            $ids = array_map('intval', array_keys($cart));
            $marks = implode(',', array_fill(0, count($ids), '?'));
            foreach (rows("SELECT * FROM products WHERE is_active = 1 AND id IN ({$marks})", $ids) as $product) {
                $qty = min((int) $cart[$product['id']], max(0, (int) $product['stock']));
                if ($qty < 1) {
                    continue;
                }
                $line = $qty * (int) $product['price_cents'];
                $items[] = ['product' => $product, 'quantity' => $qty, 'line' => $line];
                $subtotal += $line;
                $count += $qty;
            }
        }
        return ['items' => $items, 'subtotal' => $subtotal, 'count' => $count];
    }

    public function add(): void
    {
        $product = row('SELECT * FROM products WHERE id = ? AND is_active = 1', [(int) input('product_id')]);
        if (!$product) {
            $this->notFound();
        }
        $qty = max(1, min(10, (int) input('quantity', '1')));
        $current = (int) ($_SESSION['cart'][$product['id']] ?? 0);
        $_SESSION['cart'][$product['id']] = min($current + $qty, (int) $product['stock'], 10);
        flash('success', "{$product['name']} added to your cart.");
        redirect('/store/cart');
    }

    public function update(): void
    {
        foreach ((array) ($_POST['quantities'] ?? []) as $id => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                unset($_SESSION['cart'][(int) $id]);
            } else {
                $_SESSION['cart'][(int) $id] = min($qty, 10);
            }
        }
        if (($remove = (int) input('remove')) > 0) {
            unset($_SESSION['cart'][$remove]);
        }
        flash('success', 'Cart updated.');
        redirect('/store/cart');
    }

    public function cart(): void
    {
        $this->view('store/cart', ['title' => 'Your cart', 'cart' => self::cartSummary()]);
    }

    public function checkout(): void
    {
        $cart = self::cartSummary();
        if ($cart['count'] === 0) {
            redirect('/store/cart');
        }
        $this->view('store/checkout', ['title' => 'Checkout', 'cart' => $cart]);
    }

    public function placeOrder(): void
    {
        $cart = self::cartSummary();
        if ($cart['count'] === 0) {
            redirect('/store/cart');
        }
        $v = (new Validator($_POST))
            ->required('customer_name', 'Name')->max('customer_name', 120, 'Name')
            ->required('email', 'Email')->email('email')
            ->required('address', 'Address')->max('address', 255, 'Address')
            ->required('city', 'City')->required('postal_code', 'Postal code')->max('postal_code', 12, 'Postal code');
        if ($v->fails()) {
            $this->failed($v->firstError(), '/store/checkout');
        }

        $reference = 'CZA-' . strtoupper(bin2hex(random_bytes(4)));
        db()->beginTransaction();
        q('INSERT INTO orders (reference, customer_name, email, phone, address, city, postal_code, subtotal_cents) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            $reference, input('customer_name'), input('email'), input('phone'), input('address'), input('city'), input('postal_code'), $cart['subtotal'],
        ]);
        $orderId = (int) db()->lastInsertId();
        foreach ($cart['items'] as $item) {
            q('INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents) VALUES (?, ?, ?, ?, ?, ?)', [
                $orderId, $item['product']['id'], $item['product']['name'], $item['product']['price_cents'], $item['quantity'], $item['line'],
            ]);
            q('UPDATE products SET stock = stock - ? WHERE id = ?', [$item['quantity'], $item['product']['id']]);
        }
        db()->commit();
        log_activity('order.created', "Order {$reference} placed");

        $_SESSION['cart'] = [];
        $_SESSION['orders'][] = $reference;
        redirect('/store/order/' . $reference);
    }

    public function confirmation(string $reference): void
    {
        // Only the browser session that placed the order can view its confirmation.
        if (!in_array($reference, $_SESSION['orders'] ?? [], true)) {
            $this->notFound();
        }
        $order = row('SELECT * FROM orders WHERE reference = ?', [$reference]);
        if (!$order) {
            $this->notFound();
        }
        $this->view('store/confirmation', [
            'title' => 'Order received',
            'order' => $order,
            'items' => rows('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]),
        ]);
    }
}

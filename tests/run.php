<?php
declare(strict_types=1);

/*
 * Zero-dependency test runner. Uses an in-memory SQLite database seeded with demo data.
 *   php tests/run.php
 */

putenv('DB_PATH=:memory:');
putenv('DB_AUTO_SETUP=true');
putenv('DEMO_PASSWORD=test-password-123');
require dirname(__DIR__) . '/app/bootstrap.php';

$_SESSION = [];
$results = ['pass' => 0, 'fail' => 0];

function test(string $name, callable $fn): void
{
    global $results;
    try {
        db()->beginTransaction();
        $fn();
        echo "  \033[32m✓\033[0m {$name}" . PHP_EOL;
        $results['pass']++;
    } catch (Throwable $e) {
        echo "  \033[31m✗\033[0m {$name}" . PHP_EOL . "      " . $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")" . PHP_EOL;
        $results['fail']++;
    } finally {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $_SESSION = [];
        (new ReflectionClass(App\Services\Auth::class))->setStaticPropertyValue('user', null);
        (new ReflectionClass(App\Services\Auth::class))->setStaticPropertyValue('loaded', false);
    }
}

function assert_true(bool $condition, string $message = 'Assertion failed'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assert_throws(callable $fn, string $message = 'Expected an exception'): void
{
    try {
        $fn();
    } catch (Throwable) {
        return;
    }
    throw new RuntimeException($message);
}

foreach (glob(__DIR__ . '/*Test.php') as $file) {
    echo PHP_EOL . basename($file, '.php') . PHP_EOL;
    require $file;
}

echo PHP_EOL . "{$results['pass']} passed, {$results['fail']} failed" . PHP_EOL;
exit($results['fail'] > 0 ? 1 : 0);

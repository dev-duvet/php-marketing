<?php
declare(strict_types=1);

/*
 * Usage:
 *   php database/migrate.php            run pending migrations
 *   php database/migrate.php --seed     run migrations, then load demo data
 *   php database/migrate.php --fresh    delete the database, rebuild it and load demo data
 */

putenv('DB_AUTO_SETUP=false');
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Database;
use App\Services\Seeder;

$args = array_slice($argv, 1);
$fresh = in_array('--fresh', $args, true);

if ($fresh) {
    Database::reset();
    echo "Dropped database at " . Database::path() . PHP_EOL;
}

Database::migrate();
echo "Migrations complete." . PHP_EOL;

if ($fresh || in_array('--seed', $args, true)) {
    if ((int) scalar('SELECT COUNT(*) FROM users') > 0) {
        echo "Database already has data — use --fresh to rebuild it with demo data." . PHP_EOL;
        exit(0);
    }
    Seeder::run();
    echo "Demo data loaded. Sign in at /admin with the demo accounts listed in README.md." . PHP_EOL;
}

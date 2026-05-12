<?php

declare(strict_types=1);

$dbPath = __DIR__ . '/../database/cuk_admin.sqlite';

return [
    'driver' => 'sqlite',
    'database' => 'database/cuk_admin.sqlite',
    'foreign_keys' => true,
];
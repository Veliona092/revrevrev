<?php

declare(strict_types=1);

$envVars = getenv();
$lines = [];

foreach ($envVars as $key => $value) {
    if (! is_string($value)) {
        continue;
    }

    if (preg_match('/^(APP_|DB_|DATABASE_|MYSQL|SESSION_|CACHE_|MAIL_|QUEUE_|LOG_|CLOUDFLARE_|PORT)/', $key)) {
        // Escape backslashes and double quotes
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
        $lines[] = "{$key}=\"{$escaped}\"";
    }
}

file_put_contents('/var/www/html/.env', implode("\n", $lines) . "\n");

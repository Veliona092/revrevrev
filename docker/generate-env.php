<?php

declare(strict_types=1);

$envVars = getenv();
$lines = [];

foreach ($envVars as $key => $value) {
    if (! is_string($value)) {
        continue;
    }

    if (preg_match('/^(APP_|DB_|DATABASE_|MYSQL|SESSION_|CACHE_|MAIL_|QUEUE_|LOG_|CLOUDFLARE_|PORT)/', $key)) {
        // Strip any surrounding quotes the user may have pasted in Railway UI
        $cleanValue = trim($value);
        if ((str_starts_with($cleanValue, '"') && str_ends_with($cleanValue, '"')) ||
            (str_starts_with($cleanValue, "'") && str_ends_with($cleanValue, "'"))) {
            $cleanValue = substr($cleanValue, 1, -1);
        }

        // Escape backslashes and double quotes
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $cleanValue);
        $lines[] = "{$key}=\"{$escaped}\"";
    }
}

file_put_contents('/var/www/html/.env', implode("\n", $lines)."\n");

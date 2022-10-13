<?php

/** Подключение .env-файла */

if (!file_exists(__DIR__ . '/.env')) {
    die('.env not found');
}

foreach (explode(PHP_EOL, file_get_contents(__DIR__ . '/.env') ?? []) as $value) {
    if (!empty(trim($value)) && substr($value, 0, 1) !== '#') {
        putenv($value);
    }
}
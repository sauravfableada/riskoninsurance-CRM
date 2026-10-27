<?php

use Illuminate\Support\Str;

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        return $normalized === '' ? url('/') . '/' : url($normalized);
    }
}

if (!function_exists('esc')) {
    function esc($value): string
    {
        return e($value);
    }
}

<?php

namespace App\Services;

class WorkmenCompensationType
{
    public const NAME = 'Workmen Compensation (WC)';

    public static function evidence(string $text): ?string
    {
        $pattern = '/\b(?:(?:workmen[\x{2019}\x{0027}]?s?|workers?[\x{2019}\x{0027}]?s?|employees?[\x{2019}\x{0027}]?s?)\s+compensation\s+(?:insurance|policy|cover)|w\.?\s*c\.?\s+(?:insurance|policy))\b/iu';
        return preg_match($pattern, $text, $match) ? $match[0] : null;
    }

    public static function alias(string $value): bool
    {
        if (preg_match('/^\s*w\.?\s*c\.?\s*(?:policy|insurance)?\s*$/i', $value)) return true;
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value);
        $value = trim(preg_replace('/\b(?:insurance|policy|wc)\b|\s+/u', ' ', $value));
        $value = trim(preg_replace('/\s+/', ' ', $value));
        return (bool) preg_match('/^(?:workmen|workmens|worker|workers|employee|employees)(?: s)? compensation$/', $value);
    }
}

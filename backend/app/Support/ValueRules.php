<?php

namespace App\Support;

class ValueRules
{
    public static function positiveId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if ($value === null || (!is_string($value) && !is_numeric($value))) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    public static function positiveInt(mixed $value, int $max = PHP_INT_MAX): ?int
    {
        if (is_int($value)) {
            return $value > 0 && $value <= $max ? $value : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => $max,
            ],
        ]);

        return $number === false ? null : $number;
    }

    public static function intInRange(mixed $value, int $min, int $max): ?int
    {
        if (is_int($value)) {
            $number = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $number = (int) $value;
        } else {
            return null;
        }

        return ($number >= $min && $number <= $max) ? $number : null;
    }

    public static function parseIsoDate(mixed $value, bool $trim = false): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        if ($trim) {
            $value = trim($value);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
            return null;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
    }

    public static function isValidIsoDate(mixed $value): bool
    {
        return self::parseIsoDate($value) !== null;
    }

    public static function cleanText(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    public static function cleanScalarText(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        return trim(strip_tags((string) $value));
    }

    public static function normalizeCode(mixed $value, int $maxLength): ?string
    {
        $value = self::cleanScalarText($value);
        if ($value === null || $value === '' || mb_strlen($value) > $maxLength) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/u', $value) === 1 ? $value : null;
    }

    public static function normalizeDocumentNumber(mixed $value, int $maxLength): ?string
    {
        $value = self::cleanScalarText($value);
        if ($value === null || $value === '' || mb_strlen($value) > $maxLength) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9 .\/_-]*$/u', $value) === 1 ? $value : null;
    }

    public static function normalizeDigits(mixed $value, int $maxLength): ?string
    {
        $value = self::cleanScalarText($value);
        if ($value === null || $value === '' || mb_strlen($value) > $maxLength) {
            return null;
        }

        return ctype_digit($value) ? $value : null;
    }

    public static function notPastIsoDate(mixed $value): ?string
    {
        $date = self::parseIsoDate($value, true);

        return ($date !== null && $date >= date('Y-m-d')) ? $date : null;
    }

    public static function stringifyScalar(mixed $value): ?string
    {
        if ($value === null || (!is_string($value) && !is_numeric($value))) {
            return null;
        }

        return trim((string) $value);
    }
}

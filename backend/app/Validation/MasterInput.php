<?php

namespace App\Validation;

use App\Support\ValueRules;
use Throwable;

class MasterInput
{
    public static function sanitize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = strip_tags($value);
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function cleanRequired(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = self::sanitize(trim($value));

        return ($value === null || $value === '') ? null : $value;
    }

    public static function cleanOptional(mixed $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return false;
        }

        $value = self::sanitize(trim($value));

        return ($value === null || $value === '') ? null : $value;
    }

    public static function resolveRackStatus(array $data, string $fallback): array
    {
        if (!array_key_exists('status', $data)) {
            return [$fallback, null];
        }

        $status = $data['status'];
        if (!is_string($status)) {
            return [null, 'Status rak tidak valid'];
        }

        $status = trim($status);
        if (!in_array($status, ['AKTIF', 'NONAKTIF'], true)) {
            return [null, 'Status rak tidak valid'];
        }

        return [$status, null];
    }

    public static function resolveSupplierStatus(array $data, ?string $fallback = null): array
    {
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
            if (!is_string($status) || !in_array($status, ['AKTIF', 'NONAKTIF'], true)) {
                return [null, 'Status supplier tidak valid'];
            }

            return [$status, null];
        }

        if (array_key_exists('is_active', $data)) {
            $active = $data['is_active'];
            if ($active === 1 || $active === true) {
                return ['AKTIF', null];
            }
            if ($active === 0 || $active === false) {
                return ['NONAKTIF', null];
            }

            return [null, 'Status supplier tidak valid'];
        }

        return [$fallback, null];
    }

    public static function toPositiveInt(mixed $value, int $default): int
    {
        if (!is_scalar($value)) {
            return $default;
        }

        return max(1, (int) $value);
    }

    public static function parseInteger(mixed $value, int $min, int $max): ?int
    {
        return ValueRules::intInRange($value, $min, $max);
    }

    public static function isValidIsoDate(mixed $value): bool
    {
        return ValueRules::isValidIsoDate($value);
    }

    public static function isDuplicateConflict(Throwable $e, string $needle): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Duplicate entry') && str_contains($message, $needle);
    }
}

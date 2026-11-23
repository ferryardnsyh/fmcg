<?php

namespace App\Support;

final class WmsPolicy
{
    public const ROLE_SUPERADMIN = 'SUPERADMIN';
    public const ROLE_ADMIN      = 'ADMIN';
    public const ROLE_GUDANG     = 'GUDANG';

    public const ALLOWED_ROLES = [
        self::ROLE_SUPERADMIN,
        self::ROLE_ADMIN,
        self::ROLE_GUDANG,
    ];

    public const ADMIN_ROLES = [
        self::ROLE_SUPERADMIN,
        self::ROLE_ADMIN,
    ];

    public const STATUS_ACTIVE    = 'AKTIF';
    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_APPROVED  = 'APPROVED';
    public const STATUS_REJECTED  = 'REJECTED';

    public const TYPE_INCREASE = 'INCREASE';
    public const TYPE_DECREASE = 'DECREASE';
    public const TYPE_IN       = 'IN';
    public const TYPE_OUT      = 'OUT';

    public const FAILURE_NO_SESSION = 'no_session';
    public const FAILURE_INVALID_ID = 'invalid_id';
    public const FAILURE_NOT_FOUND  = 'not_found';
    public const FAILURE_INACTIVE   = 'inactive';
    public const FAILURE_OK         = 'ok';

    public const MESSAGE_UNAUTHORIZED        = 'Unauthorized. Silakan login terlebih dahulu.';
    public const MESSAGE_INACTIVE_ACCOUNT    = 'Akun sudah dinonaktifkan. Silakan hubungi administrator.';
    public const MESSAGE_SIMPLE_UNAUTHORIZED = 'Unauthorized';
    public const MESSAGE_FORBIDDEN_INBOUND   = 'Forbidden. Anda tidak memiliki akses ke transaksi inbound.';
    public const MESSAGE_FORBIDDEN_OUTBOUND  = 'Forbidden. Anda tidak memiliki akses ke transaksi outbound.';

    public static function normalizeRole(mixed $role): string
    {
        return strtoupper(trim((string) $role));
    }
}

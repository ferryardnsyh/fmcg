<?php

namespace App\Services;

use App\Auth\AuthContext;
use Config\Database;

class OutboundActorResolver
{
    private const ALLOWED_ROLES = ['SUPERADMIN', 'ADMIN', 'GUDANG'];

    protected $db;

    protected AuthContext $context;

    public function __construct($db = null, ?AuthContext $context = null)
    {
        $this->db      = $db ?? Database::connect('default');
        $this->context = $context ?? new AuthContext($this->db);
    }

    public function resolve(): array
    {
        if (!session()->get('logged_in')) {
            return $this->fail('Unauthorized. Silakan login terlebih dahulu.', 401);
        }

        $user   = $this->context->current();
        $reason = $this->context->failureCode();

        if ($reason === 'invalid_id') {
            return $this->fail('Unauthorized. Silakan login terlebih dahulu.', 401);
        }

        if ($user === null) {
            session()->destroy();

            return $this->fail('Akun sudah dinonaktifkan. Silakan hubungi administrator.', 401);
        }

        $actor = $user->toActor();

        if (!in_array($actor['role'], self::ALLOWED_ROLES, true)) {
            return $this->fail('Forbidden. Anda tidak memiliki akses ke transaksi outbound.', 403);
        }

        return [
            'status' => true,
            'code'   => 200,
            'actor'  => $actor,
        ];
    }

    private function fail(string $message, int $code): array
    {
        return [
            'status'  => false,
            'message' => $message,
            'code'    => $code,
        ];
    }
}

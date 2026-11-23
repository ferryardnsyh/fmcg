<?php

namespace App\Services;

use App\Auth\AuthContext;
use App\Support\WmsPolicy;
use Config\Database;

class OutboundActorResolver
{
    protected $db;

    protected AuthContext $context;

    public function __construct($db = null, ?AuthContext $context = null)
    {
        $this->db      = $db ?? Database::connect('default');
        $this->context = $context ?? new AuthContext($this->db);
    }

    public function resolve(): array
    {
        if (!$this->context->hasSession()) {
            return $this->fail(WmsPolicy::MESSAGE_UNAUTHORIZED, 401);
        }

        $user   = $this->context->current();
        $reason = $this->context->failureCode();

        if ($reason === WmsPolicy::FAILURE_INVALID_ID) {
            return $this->fail(WmsPolicy::MESSAGE_UNAUTHORIZED, 401);
        }

        if ($user === null) {
            session()->destroy();

            return $this->fail(WmsPolicy::MESSAGE_INACTIVE_ACCOUNT, 401);
        }

        $actor = $user->toActor();

        if (!in_array($actor['role'], WmsPolicy::ALLOWED_ROLES, true)) {
            return $this->fail(WmsPolicy::MESSAGE_FORBIDDEN_OUTBOUND, 403);
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

<?php

namespace App\Services\Concerns;

use App\Support\WmsPolicy;

trait ValidatesServiceActors
{
    private function validateServiceActor(
        ?array $actor,
        string $forbiddenMessage,
        bool $codeFirst,
        string $idNormalizer
    ): array {
        if ($actor === null) {
            return [null, $this->actorFailureResult(WmsPolicy::MESSAGE_UNAUTHORIZED, 401, $codeFirst)];
        }

        $id = $this->{$idNormalizer}($actor['id'] ?? null);
        if ($id === null) {
            return [null, $this->actorFailureResult(WmsPolicy::MESSAGE_UNAUTHORIZED, 401, $codeFirst)];
        }

        $role = WmsPolicy::normalizeRole($actor['role'] ?? null);
        if (!in_array($role, WmsPolicy::ALLOWED_ROLES, true)) {
            return [null, $this->actorFailureResult($forbiddenMessage, 403, $codeFirst)];
        }

        return [['id' => $id, 'role' => $role], null];
    }

    private function rejectAbsentActor(?array $actor): ?array
    {
        if ($actor === null) {
            return [
                'status'  => false,
                'message' => WmsPolicy::MESSAGE_SIMPLE_UNAUTHORIZED,
            ];
        }

        return null;
    }

    private function actorFailureResult(string $message, int $code, bool $codeFirst): array
    {
        if ($codeFirst) {
            return [
                'status'  => false,
                'code'    => $code,
                'message' => $message,
            ];
        }

        return [
            'status'  => false,
            'message' => $message,
            'code'    => $code,
        ];
    }
}

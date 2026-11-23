<?php

namespace App\Auth;

use App\Support\WmsPolicy;

class CurrentUser
{
    private int $idUser;
    private string $name;
    private string $username;
    private string $role;
    private string $status;

    private function __construct(int $idUser, string $name, string $username, string $role, string $status)
    {
        $this->idUser   = $idUser;
        $this->name     = $name;
        $this->username = $username;
        $this->role     = $role;
        $this->status   = $status;
    }

    public static function fromArray(array $user): self
    {
        $id = $user['id_user'] ?? $user['id'] ?? 0;

        return new self(
            (int) $id,
            (string) ($user['name'] ?? ''),
            (string) ($user['username'] ?? ''),
            WmsPolicy::normalizeRole($user['role'] ?? ''),
            (string) ($user['status'] ?? WmsPolicy::STATUS_ACTIVE)
        );
    }

    public function toArray(): array
    {
        return [
            'id_user'  => $this->idUser,
            'id'       => $this->idUser,
            'name'     => $this->name,
            'username' => $this->username,
            'role'     => $this->role,
            'status'   => $this->status,
        ];
    }

    public function toActor(): array
    {
        return [
            'id'   => $this->idUser,
            'role' => $this->role,
        ];
    }
}

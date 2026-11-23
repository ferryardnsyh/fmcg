<?php

namespace App\Auth;

use App\Support\WmsPolicy;
use Config\Database;
use RuntimeException;

class AuthContext
{
    private const ROLE_HIERARCHY = [
        WmsPolicy::ROLE_SUPERADMIN => [WmsPolicy::ROLE_SUPERADMIN],
        WmsPolicy::ROLE_ADMIN      => [WmsPolicy::ROLE_SUPERADMIN, WmsPolicy::ROLE_ADMIN],
        WmsPolicy::ROLE_GUDANG     => [WmsPolicy::ROLE_SUPERADMIN, WmsPolicy::ROLE_ADMIN, WmsPolicy::ROLE_GUDANG],
    ];

    protected $db;

    private ?CurrentUser $user = null;
    private ?string $cacheKey = null;
    private bool $primed = false;
    private string $failure = WmsPolicy::FAILURE_NO_SESSION;

    public function __construct($db = null, ?CurrentUser $user = null)
    {
        $this->db = $db ?? Database::connect('default');

        if ($user !== null) {
            $this->primeUser($user);
        }
    }

    public function hasSession(): bool
    {
        return (bool) session()->get('logged_in');
    }

    public function current(): ?CurrentUser
    {
        if ($this->primed && $this->user !== null) {
            $this->failure = WmsPolicy::FAILURE_OK;

            return $this->user;
        }

        $key = $this->sessionKey();

        if ($this->user !== null && $this->cacheKey === $key) {
            $this->failure = WmsPolicy::FAILURE_OK;

            return $this->user;
        }

        $this->user     = null;
        $this->cacheKey = null;
        $this->failure  = WmsPolicy::FAILURE_NO_SESSION;

        if (!$this->hasSession()) {
            return null;
        }

        $id = filter_var(session('id_user'), FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        if ($id === false) {
            $this->failure = WmsPolicy::FAILURE_INVALID_ID;

            return null;
        }

        $row = $this->db
            ->table('users')
            ->select('id_user, name, username, role, status')
            ->where('id_user', $id)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$row) {
            $this->failure = WmsPolicy::FAILURE_NOT_FOUND;

            return null;
        }

        if (($row['status'] ?? null) !== WmsPolicy::STATUS_ACTIVE) {
            $this->failure = WmsPolicy::FAILURE_INACTIVE;

            return null;
        }

        $this->user     = CurrentUser::fromArray($row);
        $this->cacheKey = $key;
        $this->failure  = WmsPolicy::FAILURE_OK;

        return $this->user;
    }

    public function require(): CurrentUser
    {
        $user = $this->current();

        if ($user === null) {
            throw new RuntimeException('Unauthorized');
        }

        return $user;
    }

    public function id(): int
    {
        return $this->require()->toArray()['id_user'];
    }

    public function name(): string
    {
        return $this->require()->toArray()['name'];
    }

    public function username(): string
    {
        return $this->require()->toArray()['username'];
    }

    public function role(): string
    {
        return $this->require()->toArray()['role'];
    }

    public function allows(string|array $required): bool
    {
        $user = $this->current();

        if ($user === null) {
            return false;
        }

        $role = $user->toArray()['role'];

        foreach ((array) $required as $requiredRole) {
            $requiredRole = WmsPolicy::normalizeRole($requiredRole);

            if (isset(self::ROLE_HIERARCHY[$requiredRole]) && in_array($role, self::ROLE_HIERARCHY[$requiredRole], true)) {
                return true;
            }
        }

        return false;
    }

    public function canAssignRole(string $role): bool
    {
        $user = $this->current();

        if ($user === null) {
            return false;
        }

        $target = WmsPolicy::normalizeRole($role);

        return match ($user->toArray()['role']) {
            WmsPolicy::ROLE_SUPERADMIN => true,
            WmsPolicy::ROLE_ADMIN      => in_array($target, [WmsPolicy::ROLE_ADMIN, WmsPolicy::ROLE_GUDANG], true),
            default                    => false,
        };
    }

    public function prime(array $user): void
    {
        $this->resetState();
        $this->primeUser(CurrentUser::fromArray($user));
    }

    public function refresh(): void
    {
        $this->resetState();
        $this->current();
    }

    public function clear(): void
    {
        $this->resetState();
    }

    public function failureCode(): string
    {
        $this->current();

        return $this->failure;
    }

    private function primeUser(CurrentUser $user): void
    {
        if ($user->toArray()['status'] !== WmsPolicy::STATUS_ACTIVE) {
            $this->failure = WmsPolicy::FAILURE_INACTIVE;

            return;
        }

        $this->user     = $user;
        $this->cacheKey = null;
        $this->primed   = true;
        $this->failure  = WmsPolicy::FAILURE_OK;
    }

    private function resetState(): void
    {
        $this->user     = null;
        $this->cacheKey = null;
        $this->primed   = false;
        $this->failure  = WmsPolicy::FAILURE_NO_SESSION;
    }

    private function sessionKey(): string
    {
        $loggedIn = session()->get('logged_in') ? '1' : '0';
        $id       = session('id_user');

        if (is_int($id) || is_string($id)) {
            return $loggedIn . ':' . $id;
        }

        return $loggedIn . ':' . gettype($id);
    }
}

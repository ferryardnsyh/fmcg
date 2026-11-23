<?php

namespace App\Auth;

use Config\Database;
use RuntimeException;

class AuthContext
{
    private const ROLE_HIERARCHY = [
        'SUPERADMIN' => ['SUPERADMIN'],
        'ADMIN'      => ['SUPERADMIN', 'ADMIN'],
        'GUDANG'     => ['SUPERADMIN', 'ADMIN', 'GUDANG'],
    ];

    protected $db;

    private ?CurrentUser $user = null;
    private ?string $cacheKey = null;
    private bool $primed = false;
    private string $failure = 'no_session';

    public function __construct($db = null, ?CurrentUser $user = null)
    {
        $this->db = $db ?? Database::connect('default');

        if ($user !== null) {
            $this->primeUser($user);
        }
    }

    public function current(): ?CurrentUser
    {
        if ($this->primed && $this->user !== null) {
            $this->failure = 'ok';

            return $this->user;
        }

        $key = $this->sessionKey();

        if ($this->user !== null && $this->cacheKey === $key) {
            $this->failure = 'ok';

            return $this->user;
        }

        $this->user     = null;
        $this->cacheKey = null;
        $this->failure  = 'no_session';

        if (!session()->get('logged_in')) {
            return null;
        }

        $id = filter_var(session('id_user'), FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        if ($id === false) {
            $this->failure = 'invalid_id';

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
            $this->failure = 'not_found';

            return null;
        }

        if (($row['status'] ?? null) !== 'AKTIF') {
            $this->failure = 'inactive';

            return null;
        }

        $this->user     = CurrentUser::fromArray($row);
        $this->cacheKey = $key;
        $this->failure  = 'ok';

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
            $requiredRole = strtoupper(trim((string) $requiredRole));

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

        $target = strtoupper(trim($role));

        return match ($user->toArray()['role']) {
            'SUPERADMIN' => true,
            'ADMIN'      => in_array($target, ['ADMIN', 'GUDANG'], true),
            default      => false,
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
        if ($user->toArray()['status'] !== 'AKTIF') {
            $this->failure = 'inactive';

            return;
        }

        $this->user     = $user;
        $this->cacheKey = null;
        $this->primed   = true;
        $this->failure  = 'ok';
    }

    private function resetState(): void
    {
        $this->user     = null;
        $this->cacheKey = null;
        $this->primed   = false;
        $this->failure  = 'no_session';
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

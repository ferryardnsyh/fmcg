<?php

namespace App\Services;

use App\Models\UserModel;

class AuthService
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return [
                'status' => false,
                'code' => 400,
                'message' => 'Username dan Password wajib diisi'
            ];
        }

        $user = $this->userModel
            ->where('username', $username)
            ->first();

        if (!$user) {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Username atau password salah'
            ];
        }

        if ($user['status'] !== 'AKTIF') {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Akun sudah dinonaktifkan. Silakan hubungi administrator.'
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Username atau password salah'
            ];
        }

        session()->regenerate(true);
        session()->set([
            'logged_in' => true,
            'id_user'   => $user['id_user'],
            'name'      => $user['name'],
            'username'  => $user['username'],
            'role'      => $user['role']
        ]);

        service('authContext')->prime($user);

        return [
            'status' => true,
            'code' => 200,
            'message' => 'Login berhasil',
            'data' => [
                'id'       => $user['id_user'],
                'id_user'  => $user['id_user'],
                'name'     => $user['name'],
                'username' => $user['username'],
                'role'     => $user['role']
            ]
        ];
    }

    public function logout(): array
    {
        service('authContext')->clear();
        session()->destroy();

        return [
            'status' => true,
            'message' => 'Logout berhasil'
        ];
    }

    public function me(): array
    {
        if (!session()->get('logged_in')) {
            return [
                'status' => false,
                'message' => 'Unauthorized'
            ];
        }

        $user = service('authContext')->current();
        if ($user === null) {
            return [
                'status' => false,
                'message' => 'Unauthorized'
            ];
        }

        $current = $user->toArray();

        return [
            'status' => true,
            'data' => [
                'id'       => $current['id'],
                'name'     => $current['name'],
                'username' => $current['username'],
                'role'     => $current['role']
            ]
        ];
    }
}
<?php

namespace App\Services;

use App\Auth\AuthContext;
use App\Models\UserModel;

class UserService
{
    protected UserModel $userModel;

    protected AuthContext $auth;

    private array $allowedRoles = ['SUPERADMIN', 'ADMIN', 'GUDANG'];

    public function __construct(?AuthContext $auth = null)
    {
        $this->userModel = new UserModel();
        $this->auth = $auth ?? service('authContext');
    }

    private function actor(): array
    {
        $user = $this->auth->current();

        if ($user !== null) {
            $current = $user->toArray();

            return ['id' => $current['id_user'], 'role' => $current['role']];
        }

        try {
            return ['id' => $this->auth->id(), 'role' => $this->auth->role()];
        } catch (\Throwable $e) {
            return ['id' => 0, 'role' => ''];
        }
    }

    public function getAll(): array
    {
        $users = $this->userModel
            ->select('id_user, name, username, role, status, created_at, updated_at')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return [
            'status' => true,
            'code'   => 200,
            'data'   => $users
        ];
    }

    public function getById(int $id): array
    {
        $user = $this->userModel->select('id_user, name, username, role, status, created_at, updated_at')->find($id);

        if (!$user) {
            return [
                'status'  => false,
                'code'    => 404,
                'message' => 'User tidak ditemukan'
            ];
        }

        return [
            'status' => true,
            'code'   => 200,
            'data'   => $user
        ];
    }

    public function create(array $data): array
    {
        $data['name'] = trim(strip_tags((string)($data['name'] ?? '')));
        $data['username'] = trim(strip_tags((string)($data['username'] ?? '')));

        if (empty($data['name']) || empty($data['username']) || empty($data['password'])) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Nama, username, dan password wajib diisi'
            ];
        }

        if (strlen($data['password']) < 6) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Password minimal 6 karakter'
            ];
        }

        $role = strtoupper($data['role'] ?? 'GUDANG');
        if (!in_array($role, $this->allowedRoles)) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Role tidak valid. Pilihan: ' . implode(', ', $this->allowedRoles)
            ];
        }

        if (!$this->auth->canAssignRole($role)) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Role tidak dapat ditetapkan oleh akun Anda'
            ];
        }

        $exists = $this->userModel->where('username', $data['username'])->first();
        if ($exists) {
            return [
                'status'  => false,
                'code'    => 409,
                'message' => 'Username sudah digunakan'
            ];
        }

        $userData = [
            'name'     => $data['name'],
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'     => $role,
            'status'   => in_array(strtoupper($data['status'] ?? 'AKTIF'), ['ACTIVE', 'AKTIF', '1']) ? 'AKTIF' : strtoupper($data['status'] ?? 'AKTIF')
        ];

        $id = $this->userModel->insert($userData);

        if (!$id) {
            return [
                'status'  => false,
                'code'    => 500,
                'message' => 'Gagal membuat user: ' . implode(', ', $this->userModel->errors())
            ];
        }

        return [
            'status'  => true,
            'code'    => 201,
            'message' => 'User berhasil dibuat',
            'data'    => $this->userModel->select('id_user, name, username, role, status')->find($id)
        ];
    }

    public function update(int $id, array $data): array
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return [
                'status'  => false,
                'code'    => 404,
                'message' => 'User tidak ditemukan'
            ];
        }

        $actor = $this->actor();
        $currentRole = strtoupper((string) $user['role']);

        if (!empty($data['username'])) {
            $data['username'] = trim(strip_tags((string)$data['username']));
            $exists = $this->userModel
                ->where('username', $data['username'])
                ->where('id_user !=', $id)
                ->first();
            if ($exists) {
                return [
                    'status'  => false,
                    'code'    => 409,
                    'message' => 'Username sudah digunakan'
                ];
            }
        }

        $role = null;
        if (!empty($data['role'])) {
            $role = strtoupper($data['role']);
            if (!in_array($role, $this->allowedRoles)) {
                return [
                    'status'  => false,
                    'code'    => 400,
                    'message' => 'Role tidak valid. Pilihan: ' . implode(', ', $this->allowedRoles)
                ];
            }
        }

        if ($id === $actor['id'] && $role !== null && $role !== $currentRole) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Tidak bisa mengubah role akun sendiri'
            ];
        }

        if ($currentRole === 'SUPERADMIN' && $actor['role'] !== 'SUPERADMIN') {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Hanya SUPERADMIN yang dapat mengubah akun SUPERADMIN'
            ];
        }

        if ($role === 'SUPERADMIN' && $actor['role'] !== 'SUPERADMIN') {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Hanya SUPERADMIN yang dapat menetapkan role SUPERADMIN'
            ];
        }

        if ($currentRole === 'SUPERADMIN') {
            $count = $this->userModel->where('role', 'SUPERADMIN')->countAllResults();
            if ($count <= 1) {
                if ($role !== null && $role !== 'SUPERADMIN') {
                    return [
                        'status'  => false,
                        'code'    => 400,
                        'message' => 'Tidak bisa menurunkan superadmin terakhir'
                    ];
                }

                if (!empty($data['status'])) {
                    $newStatus = strtoupper((string) $data['status']);
                    if (!in_array($newStatus, ['AKTIF', 'ACTIVE', '1'], true)) {
                        return [
                            'status'  => false,
                            'code'    => 400,
                            'message' => 'Tidak bisa menonaktifkan superadmin terakhir'
                        ];
                    }
                }
            }
        }

        $updateData = [];
        if (!empty($data['name']))     $updateData['name'] = trim(strip_tags((string)$data['name']));
        if (!empty($data['username'])) $updateData['username'] = trim(strip_tags((string)$data['username']));
        if ($role !== null)            $updateData['role'] = $role;
        if (!empty($data['status']))   $updateData['status'] = strtoupper($data['status']);

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return [
                    'status'  => false,
                    'code'    => 400,
                    'message' => 'Password minimal 6 karakter'
                ];
            }
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateData)) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Tidak ada data yang diupdate'
            ];
        }

        $this->userModel->update($id, $updateData);

        if ($id === $actor['id']) {
            $this->auth->refresh();
        }

        return [
            'status'  => true,
            'code'    => 200,
            'message' => 'User berhasil diupdate',
            'data'    => $this->userModel->select('id_user, name, username, role, status')->find($id)
        ];
    }

    public function delete(int $id): array
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return [
                'status'  => false,
                'code'    => 404,
                'message' => 'User tidak ditemukan'
            ];
        }

        // Prevent hapus diri sendiri
        $actor = $this->actor();
        if ($id === $actor['id']) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Tidak bisa menghapus akun sendiri'
            ];
        }

        if (strtoupper((string) $user['role']) === 'SUPERADMIN' && $actor['role'] !== 'SUPERADMIN') {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Hanya SUPERADMIN yang dapat menghapus akun SUPERADMIN'
            ];
        }

        // Prevent hapus superadmin terakhir
        if ($user['role'] === 'SUPERADMIN') {
            $count = $this->userModel->where('role', 'SUPERADMIN')->countAllResults();
            if ($count <= 1) {
                return [
                    'status'  => false,
                    'code'    => 400,
                    'message' => 'Tidak bisa menghapus superadmin terakhir'
                ];
            }
        }

        $this->userModel->delete($id);

        return [
            'status'  => true,
            'code'    => 200,
            'message' => 'User berhasil dihapus'
        ];
    }

    public function changePassword(int $id, array $data): array
    {
        if (empty($data['current_password']) || empty($data['new_password'])) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Password lama dan password baru wajib diisi'
            ];
        }

        if (strlen($data['new_password']) < 6) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Password baru minimal 6 karakter'
            ];
        }

        $actor = $this->actor();
        if ($id !== $actor['id'] && $actor['role'] !== 'SUPERADMIN') {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Tidak bisa mengubah password akun lain'
            ];
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return [
                'status'  => false,
                'code'    => 404,
                'message' => 'User tidak ditemukan'
            ];
        }

        // Verifikasi password lama
        if (!password_verify($data['current_password'], $user['password'])) {
            return [
                'status'  => false,
                'code'    => 400,
                'message' => 'Password lama salah'
            ];
        }

        $this->userModel->update($id, [
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
        ]);

        return [
            'status'  => true,
            'code'    => 200,
            'message' => 'Password berhasil diubah'
        ];
    }
}

<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Services\AuthService;

class LoginController extends BaseController
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login()
    {
        try {

            $data = $this->request->getJSON(true);

            if (!$data) {
                $data = $this->request->getPost();
            }

            $result = $this->authService->login(
                $data['username'] ?? '',
                $data['password'] ?? ''
            );

            return $this->response
                ->setStatusCode(($result['status'] ?? false) ? 200 : 400)
                ->setJSON($result);

        } catch (\Throwable $e) {

            log_message('error', '[LOGIN] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Terjadi kesalahan pada server'
                ]);

        }
    }

    public function me()
    {
        try {

            $user = service('authContext')->current();

            if ($user === null) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'User tidak ditemukan'
                    ]);
            }

            $current = $user->toArray();

            return $this->response->setJSON([
                'status' => true,
                'data'   => [
                    'id_user'  => $current['id_user'],
                    'name'     => $current['name'],
                    'username' => $current['username'],
                    'role'     => $current['role'],
                    'status'   => $current['status']
                ]
            ]);

        } catch (\Throwable $e) {

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Terjadi kesalahan pada server'
                ]);

        }
    }

    public function logout()
    {
        try {

            $this->authService->logout();

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Logout berhasil'
            ]);

        } catch (\Throwable $e) {

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Terjadi kesalahan pada server'
                ]);

        }
    }
}

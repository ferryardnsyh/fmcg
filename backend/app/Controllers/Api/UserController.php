<?php

namespace App\Controllers\Api;

use App\Services\UserService;

class UserController extends ApiController
{
    protected UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index()
    {
        try {
            $result = $this->userService->getAll();
            return $this->respondServiceResultWithCode($result, 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data user');
        }
    }

    public function show($id = null)
    {
        try {
            $result = $this->userService->getById($id);
            return $this->respondServiceResultWithCode($result, 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data user');
        }
    }

    public function store()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $result = $this->userService->create($data);
            return $this->respondServiceResultWithCode($result, 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $result = $this->userService->update($id, $data);
            return $this->respondServiceResultWithCode($result, 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function delete($id = null)
    {
        try {
            $result = $this->userService->delete($id);
            return $this->respondServiceResultWithCode($result, 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function changePassword($id = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $result = $this->userService->changePassword($id, $data);
            return $this->respondServiceResultWithCode($result, 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }
}

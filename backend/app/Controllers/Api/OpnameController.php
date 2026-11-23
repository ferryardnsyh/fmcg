<?php

namespace App\Controllers\Api;

use App\Services\OpnameService;

class OpnameController extends ApiController
{
    protected OpnameService $opnameService;

    public function __construct()
    {
        $this->opnameService = new OpnameService();
    }

    public function sessions()
    {
        try {
            $result = $this->opnameService->getAllSessions();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data sesi opname');
        }
    }

    public function showSession($id = null)
    {
        try {
            $result = $this->opnameService->getSessionById($id);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat detail sesi opname');
        }
    }

    public function createSession()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $actor = service('authContext')->require()->toActor();
            $result = $this->opnameService->createSession($data, $actor);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function addDetail()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $result = $this->opnameService->addDetail($data);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function completeSession($id = null)
    {
        try {
            $result = $this->opnameService->completeSession($id);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function deleteSession($id = null)
    {
        try {
            $result = $this->opnameService->deleteSession($id);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }
}

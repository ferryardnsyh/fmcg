<?php

namespace App\Controllers\Api;

use App\Services\Master\RackService;

class RackController extends ApiController
{
    protected RackService $rackService;

    public function __construct()
    {
        $this->rackService = new RackService();
    }

    public function index()
    {
        try {
            return $this->respondServiceResultWithCode(
                $this->rackService->getRacks($this->request->getGet()),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data rak', 500);
        }
    }

    public function show($id)
    {
        try {
            return $this->respondServiceResultWithCode($this->rackService->getRack($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat detail rak', 500);
        }
    }

    public function store()
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode(
                $this->rackService->createRack($this->request->getJSON(true)),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            if ($this->rackService->isDuplicateCodeConflict($e)) {
                return $this->respondException($e, 'Kode rak sudah digunakan', 409);
            }
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }

    public function update($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode(
                $this->rackService->updateRack($id, $this->request->getJSON(true)),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            if ($this->rackService->isDuplicateCodeConflict($e)) {
                return $this->respondException($e, 'Kode rak sudah digunakan', 409);
            }
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }

    public function delete($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode($this->rackService->deleteRack($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }
}

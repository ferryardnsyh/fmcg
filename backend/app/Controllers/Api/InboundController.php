<?php

namespace App\Controllers\Api;

use App\Services\InboundService;

class InboundController extends ApiController
{
    protected InboundService $inboundService;

    public function __construct()
    {
        $this->inboundService = new InboundService();
    }

    public function index()
    {
        try {
            $result = $this->inboundService->getAll();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data inbound');
        }
    }

    public function show($id = null)
    {
        try {
            $result = $this->inboundService->getById($id);
            return $this->respondServiceResultWithCode($result, 200, 404);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data inbound');
        }
    }

    public function store()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $actor = service('authContext')->require()->toActor();
            $result = $this->inboundService->create($data, $actor);
            return $this->respondServiceResultWithCode($result, 201, 400);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $result = $this->inboundService->update($id, $data);
            return $this->respondServiceResultWithCode($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function delete($id = null)
    {
        try {
            $result = $this->inboundService->delete($id);
            return $this->respondServiceResultWithCode($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function complete($id = null)
    {
        try {
            $result = $this->inboundService->complete($id);
            return $this->respondServiceResultWithCode($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function productsBySupplier($supplierId = null)
    {
        try {
            $result = $this->inboundService->getProductsBySupplier($supplierId);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data produk');
        }
    }
}

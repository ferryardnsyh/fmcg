<?php

namespace App\Controllers\Api;

use App\Services\Master\SupplierService;

class SupplierController extends ApiController
{
    protected SupplierService $supplierService;

    public function __construct()
    {
        $this->supplierService = new SupplierService();
    }

    public function index()
    {
        try {
            return $this->respondServiceResultWithCode($this->supplierService->getSuppliers(), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data supplier', 500);
        }
    }

    public function show($id)
    {
        try {
            return $this->respondServiceResultWithCode($this->supplierService->getSupplier($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data supplier', 500);
        }
    }

    public function store()
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode(
                $this->supplierService->createSupplier($this->request->getJSON(true)),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            if ($this->supplierService->isDuplicateCodeConflict($e)) {
                return $this->respondException($e, 'Kode supplier sudah digunakan', 409);
            }
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }

    public function update($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode(
                $this->supplierService->updateSupplier($id, $this->request->getJSON(true)),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            if ($this->supplierService->isDuplicateCodeConflict($e)) {
                return $this->respondException($e, 'Kode supplier sudah digunakan', 409);
            }
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }

    public function delete($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode($this->supplierService->deleteSupplier($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }
}

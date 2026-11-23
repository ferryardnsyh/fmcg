<?php

namespace App\Controllers\Api;

use App\Services\StockService;

class StockController extends ApiController
{
    protected StockService $stockService;

    public function __construct()
    {
        $this->stockService = new StockService();
    }

    public function realtime()
    {
        try {
            $result = $this->stockService->getStockRealTime();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data stok realtime');
        }
    }

    public function mutations()
    {
        try {
            $result = $this->stockService->getStockMutations();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data mutasi stok');
        }
    }

    public function lowStock()
    {
        try {
            $result = $this->stockService->getStockLow();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data stok rendah');
        }
    }

    public function adjust($id = null)
    {
        try {
            $result = $this->stockService->getStockAdjustments();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data adjustment');
        }
    }

    public function storeAdjustment()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $actor = service('authContext')->require()->toActor();
            $result = $this->stockService->adjustStock($data, $actor);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function showAdjustment($id = null)
    {
        try {
            $result = $this->stockService->getAdjustmentById($id);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat detail adjustment');
        }
    }

    public function approve($id = null)
    {
        $this->denyIfNotAdmin();
        try {
            $result = $this->stockService->approveAdjustment($id, service('authContext')->id());
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal menyetujui adjustment');
        }
    }

    public function reject($id = null)
    {
        $this->denyIfNotAdmin();
        try {
            $result = $this->stockService->rejectAdjustment($id, service('authContext')->id());
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal menolak adjustment');
        }
    }
}

<?php

namespace App\Controllers\Api;

use App\Services\Master\BatchService;

class BatchController extends ApiController
{
    protected BatchService $batchService;

    public function __construct()
    {
        $this->batchService = new BatchService();
    }

    public function index()
    {
        try {
            return $this->respondServiceResultWithCode($this->batchService->getBatches(), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data batch', 500);
        }
    }

    public function show($id)
    {
        try {
            return $this->respondServiceResultWithCode($this->batchService->getBatch($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data batch', 500);
        }
    }

    public function statistic()
    {
        try {
            return $this->respondServiceResultWithCode($this->batchService->getStatistic(), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat statistik batch', 500);
        }
    }

    public function store()
    {
        return $this->respondServiceResultWithCode($this->batchService->mutationDisabled(), 200, 400, true);
    }

    public function update($id)
    {
        return $this->respondServiceResultWithCode($this->batchService->mutationDisabled(), 200, 400, true);
    }

    public function delete($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode($this->batchService->deleteBatch($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }
}

<?php

namespace App\Controllers\Api;

use App\Services\ExpiredService;

class ExpiredController extends ApiController
{
    protected ExpiredService $expiredService;

    public function __construct()
    {
        $this->expiredService = new ExpiredService();
    }

    public function index()
    {
        try {
            $params = $this->request->getGet();
            $result = $this->expiredService->getExpiredProducts(
                $params['date_from'] ?? null,
                $params['date_to'] ?? null,
                $params['status'] ?? null
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data expired');
        }
    }

    public function nearExpiry()
    {
        try {
            $result = $this->expiredService->getNearExpiry();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data mendekati expired');
        }
    }

    public function expiredToday()
    {
        try {
            $result = $this->expiredService->getExpiredToday();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data expired hari ini');
        }
    }
}

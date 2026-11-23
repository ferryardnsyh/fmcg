<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Services\DashboardService;

class DashboardController extends BaseController
{
    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function summary()
    {
        try {
            return $this->response->setJSON(
                $this->dashboardService->summary()
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat data dashboard'
                ]);
        }
    }

    public function expiredProducts()
    {
        try {
            return $this->response->setJSON(
                $this->dashboardService->getNearExpiryProducts()
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat data produk mendekati expired'
                ]);
        }
    }

    public function chart()
    {
        try {
            return $this->response->setJSON(
                $this->dashboardService->getChartData()
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat data chart'
                ]);
        }
    }

    public function topOutbound()
    {
        try {
            return $this->response->setJSON(
                $this->dashboardService->getTopOutboundProducts()
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat data produk terlaris'
                ]);
        }
    }
}

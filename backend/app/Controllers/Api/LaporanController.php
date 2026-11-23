<?php

namespace App\Controllers\Api;

use App\Services\LaporanService;

class LaporanController extends ApiController
{
    protected LaporanService $laporanService;

    public function __construct()
    {
        $this->laporanService = new LaporanService();
    }

    public function barangMasuk()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->getLaporanBarangMasuk(
                $data['date_from'] ?? null,
                $data['date_to'] ?? null
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat laporan barang masuk');
        }
    }

    public function barangKeluar()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->getLaporanBarangKeluar(
                $data['date_from'] ?? null,
                $data['date_to'] ?? null
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat laporan barang keluar');
        }
    }

    public function stok()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->getLaporanStok();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat laporan stok');
        }
    }

    public function stokOpname()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->getLaporanStokOpname(
                $data['id_opname'] ?? ''
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat laporan stok opname');
        }
    }

    public function adjustments()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->getLaporanAdjustment(
                $data['date_from'] ?? null,
                $data['date_to'] ?? null
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat laporan adjustment');
        }
    }

    public function expired()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->getLaporanExpired(
                $data['date_from'] ?? null,
                $data['date_to'] ?? null
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat laporan expired');
        }
    }

    public function exportPdf()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getGet();
            $result = $this->laporanService->exportPdf(
                $data['type'] ?? 'barang_masuk',
                $data
            );
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal mengexport laporan');
        }
    }
}

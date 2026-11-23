<?php

namespace App\Services;

use Config\Database;

class LaporanService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function inboundReportQuery(?string $dateFrom, ?string $dateTo)
    {
        $query = $this->db
            ->table('inbound_headers ih')
            ->join('suppliers s', 's.id_supplier = ih.id_supplier', 'left')
            ->join('inbound_details id', 'id.id_inbound = ih.id_inbound', 'left');

        if (!empty($dateFrom)) {
            $query->where('ih.reception_date >=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->where('ih.reception_date <=', $dateTo);
        }

        return $query;
    }

    private function outboundReportQuery(?string $dateFrom, ?string $dateTo)
    {
        $query = $this->db
            ->table('outbound_headers oh')
            ->join('outbound_details od', 'od.id_outbound = oh.id_outbound', 'left')
            ->where('oh.status', 'COMPLETED');

        if (!empty($dateFrom)) {
            $query->where('oh.delivery_date >=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->where('oh.delivery_date <=', $dateTo);
        }

        return $query;
    }

    private function adjustmentReportQuery(?string $dateFrom, ?string $dateTo)
    {
        $query = $this->db
            ->table('stock_adjustments sa')
            ->join('product_batches pb', 'pb.id_batch = sa.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->join('users u', 'u.id_user = sa.created_by', 'left')
            ->join('users a', 'a.id_user = sa.approved_by', 'left');

        if (!empty($dateFrom)) {
            $query->where('sa.created_at >=', $dateFrom . ' 00:00:00');
        }

        if (!empty($dateTo)) {
            $query->where('sa.created_at <=', $dateTo . ' 23:59:59');
        }

        return $query;
    }

    public function getLaporanBarangMasuk(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $data = $this->inboundReportQuery($dateFrom, $dateTo)
            ->select('
                ih.id_inbound,
                ih.reception_number,
                ih.reception_date,
                ih.status,
                s.name AS supplier_name,
                s.supplier_code,
                COUNT(id.id_inbound_detail) AS total_items,
                SUM(id.quantity) AS total_quantity
            ')
            ->groupBy('ih.id_inbound, ih.reception_number, ih.reception_date, ih.status, s.name, s.supplier_code')
            ->orderBy('ih.reception_date', 'DESC')
            ->get()
            ->getResultArray();

        $totalRow = $this->inboundReportQuery($dateFrom, $dateTo)
            ->select('COALESCE(SUM(id.quantity), 0) AS grand_total_quantity')
            ->get()
            ->getRowArray();

        $grandTotal = (int) ($totalRow['grand_total_quantity'] ?? 0);

        return [
            'status' => true,
            'data' => [
                'headers' => $data,
                'grand_total_quantity' => $grandTotal,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    public function getLaporanBarangKeluar(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $data = $this->outboundReportQuery($dateFrom, $dateTo)
            ->select('
                oh.id_outbound,
                oh.delivery_number,
                oh.delivery_date,
                oh.status,
                COUNT(od.id_outbound_detail) AS total_items,
                SUM(od.quantity) AS total_quantity
            ')
            ->groupBy('oh.id_outbound, oh.delivery_number, oh.delivery_date, oh.status')
            ->orderBy('oh.delivery_date', 'DESC')
            ->get()
            ->getResultArray();

        $totalRow = $this->outboundReportQuery($dateFrom, $dateTo)
            ->select('COALESCE(SUM(od.quantity), 0) AS grand_total_quantity')
            ->get()
            ->getRowArray();

        $grandTotal = (int) ($totalRow['grand_total_quantity'] ?? 0);

        return [
            'status' => true,
            'data' => [
                'headers' => $data,
                'grand_total_quantity' => $grandTotal,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    public function getLaporanStok(): array
    {
        $data = $this->db
            ->table('products p')
            ->select('
                p.id_product,
                p.sku_code,
                p.name,
                p.minimum_stock,
                p.brand_name AS brand,
                p.category AS category_name,
                p.unit,
                COALESCE(SUM(pb.quantity), 0) AS total_available,
                COUNT(pb.id_batch) AS total_batches
            ')
            ->join('product_batches pb', 'pb.id_product = p.id_product', 'left')
            ->groupBy('p.id_product')
            ->orderBy('p.name', 'ASC')
            ->get()
            ->getResultArray();

        $totalProducts = count($data);
        $totalStock = array_sum(array_column($data, 'total_available'));

        return [
            'status' => true,
            'data' => [
                'products' => $data,
                'total_products' => $totalProducts,
                'total_stock' => $totalStock
            ]
        ];
    }

    public function getLaporanStokOpname(?string $sessionId = null): array
    {
        if (empty($sessionId)) {
            $sessions = $this->db
                ->table('stock_opname_sessions sos')
                ->select('
                    sos.*,
                    u.name AS created_by_name
                ')
                ->join('users u', 'u.id_user = sos.created_by', 'left')
                ->orderBy('sos.created_at', 'DESC')
                ->get()
                ->getResultArray();

            return [
                'status' => true,
                'data' => [
                    'sessions' => $sessions,
                    'message' => 'Pilih sesi opname untuk melihat detail laporan'
                ]
            ];
        }

        $session = $this->db
            ->table('stock_opname_sessions sos')
            ->select('
                sos.*,
                u.name AS created_by_name
            ')
            ->join('users u', 'u.id_user = sos.created_by', 'left')
            ->where('sos.id_opname', $sessionId)
            ->get()
            ->getRowArray();

        if (!$session) {
            return [
                'status' => false,
                'message' => 'Sesi opname tidak ditemukan'
            ];
        }

        $details = $this->db
            ->table('stock_opname_details sod')
            ->select('
                sod.*,
                p.name AS product_name,
                p.sku_code,
                pb.batch_number
            ')
            ->join('product_batches pb', 'pb.id_batch = sod.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('sod.id_opname', $sessionId)
            ->get()
            ->getResultArray();

        $totalDiff = array_sum(array_column($details, 'difference'));
        $itemsWithDiff = count(array_filter($details, fn($d) => (int)$d['difference'] !== 0));

        $session['details'] = $details;
        $session['total_difference'] = $totalDiff;
        $session['items_with_difference'] = $itemsWithDiff;

        return [
            'status' => true,
            'data' => $session
        ];
    }

    public function getLaporanExpired(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = $this->db
            ->table('product_batches pb')
            ->select('
                pb.id_batch,
                pb.batch_number,
                pb.expiry_date,
                pb.quantity,
                p.id_product,
                p.sku_code,
                p.name AS product_name,
                p.unit,
                DATEDIFF(pb.expiry_date, NOW()) AS days_left
            ')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('pb.quantity >', 0);

        if (!empty($dateFrom)) {
            $query->where('pb.expiry_date >=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->where('pb.expiry_date <=', $dateTo);
        }

        $data = $query->orderBy('pb.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($data as &$item) {
            $daysLeft = (int)($item['days_left'] ?? 0);
            if ($daysLeft < 0) {
                $item['status_label'] = 'EXPIRED';
            } elseif ($daysLeft <= 30) {
                $item['status_label'] = 'AKAN EXPIRED';
            } else {
                $item['status_label'] = 'OK';
            }
        }
        unset($item);

        $totalItems = count($data);
        $totalQuantity = array_sum(array_column($data, 'quantity'));

        return [
            'status' => true,
            'data' => [
                'items' => $data,
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    public function getLaporanAdjustment(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $data = $this->adjustmentReportQuery($dateFrom, $dateTo)
            ->select('
                sa.id_adjustment,
                sa.type,
                sa.quantity,
                sa.reason,
                sa.status,
                sa.created_at,
                p.name AS product_name,
                p.sku_code,
                pb.batch_number,
                u.name AS created_by_name,
                a.name AS approved_by_name
            ')
            ->orderBy('sa.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $totalRow = $this->adjustmentReportQuery($dateFrom, $dateTo)
            ->select('COALESCE(SUM(sa.quantity), 0) AS grand_total_quantity')
            ->get()
            ->getRowArray();

        $totalQuantity = (int) ($totalRow['grand_total_quantity'] ?? 0);

        return [
            'status' => true,
            'data' => [
                'headers' => $data,
                'grand_total_quantity' => $totalQuantity,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    public function exportPdf(string $type, array $params = []): array
    {
        $data = [];

        switch ($type) {
            case 'barang_masuk':
                $result = $this->getLaporanBarangMasuk(
                    $params['date_from'] ?? null,
                    $params['date_to'] ?? null
                );
                $data = $result['data'] ?? [];
                $title = 'Laporan Barang Masuk';
                break;

            case 'barang_keluar':
                $result = $this->getLaporanBarangKeluar(
                    $params['date_from'] ?? null,
                    $params['date_to'] ?? null
                );
                $data = $result['data'] ?? [];
                $title = 'Laporan Barang Keluar';
                break;

            case 'stok':
                $result = $this->getLaporanStok();
                $data = $result['data'] ?? [];
                $title = 'Laporan Stok';
                break;

            case 'stok_opname':
                $result = $this->getLaporanStokOpname($params['session_id'] ?? '');
                $data = $result['data'] ?? [];
                $title = 'Laporan Stok Opname';
                break;

            case 'adjustment':
                $result = $this->getLaporanAdjustment(
                    $params['date_from'] ?? null,
                    $params['date_to'] ?? null
                );
                $data = $result['data'] ?? [];
                $title = 'Laporan Penyesuaian Stok';
                break;

            case 'expired':
                $result = $this->getLaporanExpired(
                    $params['date_from'] ?? null,
                    $params['date_to'] ?? null
                );
                $data = $result['data'] ?? [];
                $title = 'Laporan Barang Expired';
                break;

            default:
                return [
                    'status' => false,
                    'message' => 'Tipe laporan tidak valid. Pilihan: barang_masuk, barang_keluar, stok, stok_opname, adjustment, expired'
                ];
        }

        return [
            'status' => true,
            'data' => [
                'title' => $title,
                'type' => $type,
                'params' => $params,
                'report_data' => $data,
                'generated_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
}

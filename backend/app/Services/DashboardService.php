<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DashboardService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function summary(): array
    {
        $totalProduk = $this->db
            ->table('products')
            ->where('status', 'AKTIF')
            ->countAllResults();

        $totalBatch = $this->db
            ->table('product_batches')
            ->countAllResults();

        $totalStok = $this->db
            ->table('product_batches')
            ->selectSum('quantity', 'total')
            ->get()
            ->getRowArray()['total'] ?? 0;

        $stokRendahQuery = $this->db
            ->query("
                SELECT COUNT(*) AS total FROM (
                    SELECT p.id_product, p.minimum_stock
                    FROM products p
                    LEFT JOIN product_batches pb ON pb.id_product = p.id_product
                    WHERE p.status = 'AKTIF'
                    GROUP BY p.id_product, p.minimum_stock
                    HAVING COALESCE(SUM(pb.quantity), 0) <= p.minimum_stock
                ) AS low_stock
            ")
            ->getRowArray();
        $stokRendah = (int)($stokRendahQuery['total'] ?? 0);

        $mendekatiExpired = $this->db
            ->table('product_batches')
            ->where('expiry_date >=', date('Y-m-d'))
            ->where('expiry_date <=', date('Y-m-d', strtotime('+30 days')))
            ->countAllResults();

        $barangMasukHariIni = $this->db
            ->table('inbound')
            ->where('reception_date', date('Y-m-d'))
            ->where('status', 'COMPLETED')
            ->countAllResults();

        $barangKeluarHariIni = $this->db
            ->table('outbound')
            ->where('delivery_date', date('Y-m-d'))
            ->where('status', 'COMPLETED')
            ->countAllResults();

        return [
            'status' => true,
            'data' => [
                'total_produk'               => $totalProduk,
                'total_batch'                => $totalBatch,
                'total_stok'                 => (int)$totalStok,
                'stok_rendah'                => $stokRendah,
                'mendekati_expired'          => $mendekatiExpired,
                'total_barang_masuk_hari_ini' => $barangMasukHariIni,
                'total_barang_keluar_hari_ini' => $barangKeluarHariIni,
            ]
        ];
    }

    public function getChartData(): array
    {
        $days = 14;
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-$i days"));
        }

        $inbound = $this->db->table('inbound')
            ->select("reception_date AS date, COUNT(*) AS total")
            ->where('reception_date >=', $dates[0])
            ->where('reception_date <=', $dates[$days - 1])
            ->where('status', 'COMPLETED')
            ->groupBy('reception_date')
            ->get()->getResultArray();

        $outbound = $this->db->table('outbound')
            ->select("delivery_date AS date, COUNT(*) AS total")
            ->where('delivery_date >=', $dates[0])
            ->where('delivery_date <=', $dates[$days - 1])
            ->where('status', 'COMPLETED')
            ->groupBy('delivery_date')
            ->get()->getResultArray();

        $inboundMap = [];
        foreach ($inbound as $row) {
            $inboundMap[$row['date']] = (int)$row['total'];
        }
        $outboundMap = [];
        foreach ($outbound as $row) {
            $outboundMap[$row['date']] = (int)$row['total'];
        }

        $labels = [];
        $inboundData = [];
        $outboundData = [];
        foreach ($dates as $d) {
            $labels[] = date('d M', strtotime($d));
            $inboundData[] = $inboundMap[$d] ?? 0;
            $outboundData[] = $outboundMap[$d] ?? 0;
        }

        return [
            'status' => true,
            'data' => [
                'labels'   => $labels,
                'inbound'  => $inboundData,
                'outbound' => $outboundData,
            ]
        ];
    }

    public function getTopOutboundProducts(): array
    {
        $topProducts = $this->db
            ->table('outbound_details od')
            ->select('
                p.name AS product_name,
                p.id_product,
                SUM(od.quantity) AS total_quantity,
                COUNT(DISTINCT oh.id_outbound) AS total_transactions,
                COUNT(DISTINCT pb.id_batch) AS total_batch
            ')
            ->join('product_batches pb', 'pb.id_batch = od.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->join('outbound oh', 'oh.id_outbound = od.id_outbound', 'left')
            ->where('oh.status', 'COMPLETED')
            ->groupBy('pb.id_product, p.name, p.id_product')
            ->orderBy('total_quantity', 'DESC')
            ->limit(7)
            ->get()
            ->getResultArray();

        $labels = [];
        $data = [];
        $details = [];
        foreach ($topProducts as $row) {
            $name = $row['product_name'];
            if (strlen($name) > 25) {
                $name = substr($name, 0, 22) . '...';
            }
            $labels[] = $name;
            $data[] = (int)$row['total_quantity'];
            $details[] = [
                'product_name'      => $row['product_name'],
                'total_quantity'    => (int)$row['total_quantity'],
                'total_transactions'=> (int)$row['total_transactions'],
                'total_batch'       => (int)$row['total_batch'],
            ];
        }

        return [
            'status' => true,
            'data' => [
                'labels' => $labels,
                'values' => $data,
                'details' => $details,
            ]
        ];
    }

    public function getNearExpiryProducts(): array
    {
        $data = $this->db
            ->table('product_batches pb')
            ->select('
                p.name,
                pb.batch_number,
                pb.quantity,
                pb.expiry_date,
                DATEDIFF(pb.expiry_date, NOW()) AS days_left
            ')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('pb.expiry_date >=', date('Y-m-d'))
            ->where('pb.expiry_date <=', date('Y-m-d', strtotime('+30 days')))
            ->orderBy('pb.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }
}

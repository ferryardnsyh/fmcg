<?php

namespace App\Services;

use Config\Database;

class ExpiredService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getExpiredProducts(?string $dateFrom = null, ?string $dateTo = null, ?string $status = null): array
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
                DATEDIFF(pb.expiry_date, NOW()) AS days_expired
            ')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('pb.quantity >', 0);

        if ($status === 'expired') {
            $query->where('pb.expiry_date <', date('Y-m-d'));
        } elseif ($status === 'near_expiry') {
            $query->where('pb.expiry_date >=', date('Y-m-d'))
                ->where('pb.expiry_date <=', date('Y-m-d', strtotime('+30 days')));
        }

        if (!empty($dateFrom)) {
            $query->where('pb.expiry_date >=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->where('pb.expiry_date <=', $dateTo);
        }

        $data = $query->orderBy('pb.expiry_date', 'ASC')->get()->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }

    public function getNearExpiry(int $days = 30): array
    {
        $data = $this->db
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
            ->where('pb.expiry_date >=', date('Y-m-d'))
            ->where('pb.expiry_date <=', date('Y-m-d', strtotime('+' . $days . ' days')))
            ->where('pb.quantity >', 0)
            ->orderBy('pb.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }

    public function getExpiredToday(): array
    {
        $data = $this->db
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
            ->where('pb.expiry_date =', date('Y-m-d'))
            ->where('pb.quantity >', 0)
            ->orderBy('pb.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }
}

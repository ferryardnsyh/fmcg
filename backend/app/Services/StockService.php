<?php

namespace App\Services;

use App\Services\Concerns\ManagesStockAdjustments;
use App\Services\Concerns\ServiceResults;
use App\Services\Concerns\ValidatesServiceActors;
use App\Support\WmsPolicy;
use Config\Database;

class StockService
{
    use ServiceResults;
    use ValidatesServiceActors;
    use ManagesStockAdjustments;

    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getStockRealTime(): array
    {
        $data = $this->db
            ->table('products p')
            ->select('
                p.id_product,
                p.sku_code,
                p.name,
                p.unit,
                p.minimum_stock,
                COALESCE(SUM(pb.quantity), 0) AS total_available,
                COALESCE(SUM(pb.quantity), 0) AS total_quantity,
                COUNT(pb.id_batch) AS total_batches
            ')
            ->join('product_batches pb', 'pb.id_product = p.id_product', 'left')
            ->where('p.status', WmsPolicy::STATUS_ACTIVE)
            ->groupBy('p.id_product')
            ->orderBy('p.name', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }

    public function getStockMutations(): array
    {
        $inbound = $this->db
            ->table('inbound ih')
            ->select("
                ih.id_inbound,
                ih.reception_number AS document_number,
                'INBOUND' AS type,
                ih.reception_date AS mutation_date,
                ih.status,
                ih.created_at
            ")
            ->get()
            ->getResultArray();

        $outbound = $this->db
            ->table('outbound oh')
            ->select("
                oh.id_outbound,
                oh.delivery_number AS document_number,
                'OUTBOUND' AS type,
                oh.delivery_date AS mutation_date,
                oh.status,
                oh.created_at
            ")
            ->get()
            ->getResultArray();

        $adjustments = $this->db
            ->table('stock_adjustments sa')
            ->select("
                sa.id_adjustment,
                sa.type AS document_number,
                'ADJUSTMENT' AS type,
                sa.created_at AS mutation_date,
                sa.status,
                sa.created_at
            ")
            ->where('sa.status', WmsPolicy::STATUS_APPROVED)
            ->get()
            ->getResultArray();

        $mutations = array_merge($inbound, $outbound, $adjustments);

        usort($mutations, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return [
            'status' => true,
            'data' => $mutations
        ];
    }

    public function getStockLow(): array
    {
        $data = $this->db
            ->table('products p')
            ->select('
                p.id_product,
                p.sku_code,
                p.name,
                p.unit,
                p.minimum_stock,
                COALESCE(SUM(pb.quantity), 0) AS total_available
            ')
            ->join('product_batches pb', 'pb.id_product = p.id_product', 'left')
            ->where('p.status', WmsPolicy::STATUS_ACTIVE)
            ->groupBy('p.id_product')
            ->having('total_available <= p.minimum_stock')
            ->orderBy('total_available', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }

    public function adjustStock(array $data, ?array $actor = null): array
    {
        $error = $this->rejectAbsentActor($actor);
        if ($error !== null) {
            return $error;
        }

        [$payload, $error] = $this->prepareAdjustmentInput($data);
        if ($error !== null) {
            return $error;
        }

        return $this->runServiceTransaction(
            fn () => $this->insertPendingAdjustment($payload, $actor),
            'StockService::adjustStock',
            'Gagal membuat penyesuaian stock',
            null
        );
    }

    public function approveAdjustment(int $id, ?int $approvedBy = null): array
    {
        $this->db->transBegin();

        try {
            [$adjustment, $error] = $this->lockAdjustmentForDecision(
                $id,
                'Hanya adjustment dengan status PENDING yang bisa disetujui',
                true
            );
            if ($error !== null) {
                return $error;
            }

            [$batchRow, $error] = $this->lockAdjustmentBatch($adjustment['id_batch']);
            if ($error !== null) {
                return $error;
            }

            return $this->applyApprovedAdjustment($id, $approvedBy, $adjustment, $batchRow);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'StockService::approveAdjustment: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return $this->makeFailureResult('Gagal menyetujui adjustment');
        }
    }

    public function rejectAdjustment(int $id, ?int $rejectedBy = null): array
    {
        $this->db->transBegin();

        try {
            [$adjustment, $error] = $this->lockAdjustmentForDecision(
                $id,
                'Hanya adjustment dengan status PENDING yang bisa ditolak',
                false
            );
            if ($error !== null) {
                return $error;
            }

            return $this->rejectAdjustmentRecord($id, $rejectedBy);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'StockService::rejectAdjustment: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return $this->makeFailureResult('Gagal menolak adjustment');
        }
    }

    public function getStockAdjustments(): array
    {
        $data = $this->db
            ->table('stock_adjustments sa')
            ->select('
                sa.*,
                pb.id_product,
                p.name AS product_name,
                p.sku_code,
                u.name AS created_by_name,
                a.name AS approved_by_name
            ')
            ->join('product_batches pb', 'pb.id_batch = sa.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->join('users u', 'u.id_user = sa.created_by', 'left')
            ->join('users a', 'a.id_user = sa.approved_by', 'left')
            ->orderBy('sa.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data
        ];
    }

    public function getAdjustmentById(int $id): array
    {
        $data = $this->db
            ->table('stock_adjustments sa')
            ->select('
                sa.*,
                pb.id_product,
                p.name AS product_name,
                p.sku_code,
                u.name AS created_by_name,
                a.name AS approved_by_name
            ')
            ->join('product_batches pb', 'pb.id_batch = sa.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->join('users u', 'u.id_user = sa.created_by', 'left')
            ->join('users a', 'a.id_user = sa.approved_by', 'left')
            ->where('sa.id_adjustment', $id)
            ->get()
            ->getRowArray();

        if (!$data) {
            return $this->makeFailureResult('Adjustment tidak ditemukan');
        }

        return [
            'status' => true,
            'data' => $data
        ];
    }

    public function getStockByProduct(string $productId): array
    {
        $product = $this->db->table('products p')
            ->select('p.*')
            ->where('p.id_product', $productId)
            ->get()
            ->getRowArray();

        if (!$product) {
            return $this->makeFailureResult('Produk tidak ditemukan');
        }

        $batches = $this->db
            ->table('product_batches pb')
            ->select('
                pb.*,
                rl.rack_code AS rack_location_code
            ')
            ->join('rack rl', 'rl.id_rack = pb.id_rack', 'left')
            ->where('pb.id_product', $productId)
            ->orderBy('pb.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        $totalQuantity = array_sum(array_column($batches, 'quantity'));

        $product['batches'] = $batches;
        $product['total_available'] = $totalQuantity;
        $product['total_quantity'] = $totalQuantity;

        return [
            'status' => true,
            'data' => $product
        ];
    }
}

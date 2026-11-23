<?php

namespace App\Services;

use App\Validation\MasterInput;
use Config\Database;

class StockService
{
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
            ->where('p.status', 'AKTIF')
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
            ->table('inbound_headers ih')
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
            ->table('outbound_headers oh')
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
            ->where('sa.status', 'APPROVED')
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
            ->where('p.status', 'AKTIF')
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
        if ($actor === null) {
            return [
                'status' => false,
                'message' => 'Unauthorized'
            ];
        }

        $data['reason'] = isset($data['reason']) ? trim(strip_tags((string)$data['reason'])) : null;

        if (empty($data['product_id']) && empty($data['batch_id'])) {
            return [
                'status' => false,
                'message' => 'Product ID wajib diisi'
            ];
        }

        if (isset($data['type']) || isset($data['quantity'])) {
            $rawType = $data['type'] ?? '';
            if (!in_array($rawType, ['INCREASE', 'DECREASE', 'IN', 'OUT'], false)) {
                return [
                    'status' => false,
                    'message' => 'Tipe adjustment harus INCREASE atau DECREASE'
                ];
            }
            $type = in_array($rawType, ['INCREASE', 'IN'], false) ? 'INCREASE' : 'DECREASE';

            $qty = $data['quantity'] ?? null;
            $intQty = filter_var($qty, FILTER_VALIDATE_INT);
            if ($intQty === false || $intQty <= 0) {
                return [
                    'status' => false,
                    'message' => 'Quantity adjustment wajib diisi berupa bilangan bulat positif'
                ];
            }

            if (empty($data['reason']) || trim((string)$data['reason']) === '') {
                return [
                    'status' => false,
                    'message' => 'Alasan adjustment wajib diisi'
                ];
            }
        }

        $batchId = $data['batch_id'] ?? null;

        if (empty($batchId)) {
            $batch = $this->db->table('product_batches')
                ->where('id_product', $data['product_id'])
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getRowArray();

            if (!$batch) {
                return [
                    'status' => false,
                    'message' => 'Tidak ditemukan batch untuk produk ini'
                ];
            }
            $batchId = $batch['id_batch'];
            $data['product_id'] = $batch['id_product'];
            $quantityBefore = (int)$batch['quantity'];
        } else {
            $batch = $this->db->table('product_batches')
                ->where('id_batch', $batchId)
                ->get()
                ->getRowArray();

            if (!$batch) {
                return [
                    'status' => false,
                    'message' => 'Batch tidak ditemukan'
                ];
            }
            $data['product_id'] = $batch['id_product'];
            $quantityBefore = (int)$batch['quantity'];
        }

        // Support both absolute (new_quantity) and delta (type + quantity) formats
        if (isset($data['new_quantity'])) {
            $newQuantity = MasterInput::parseInteger($data['new_quantity'], 0, 2147483647);
            if ($newQuantity === null) {
                return [
                    'status' => false,
                    'message' => 'Quantity baru harus bilangan bulat antara 0 dan 2147483647'
                ];
            }
        } elseif (!empty($data['type']) && isset($data['quantity'])) {
            $delta = $intQty;
            if ($type === 'DECREASE') {
                $newQuantity = $quantityBefore - $delta;
            } else {
                $newQuantity = $quantityBefore + $delta;
            }
        } else {
            return [
                'status' => false,
                'message' => 'Product ID dan quantity baru wajib diisi'
            ];
        }

        $diff = $newQuantity - $quantityBefore;

        if ($newQuantity < 0) {
            return [
                'status' => false,
                'message' => 'Quantity tidak boleh negatif'
            ];
        }

        if ($diff === 0) {
            return [
                'status' => false,
                'message' => 'Quantity tidak berubah'
            ];
        }

        $adjType = $diff >= 0 ? 'INCREASE' : 'DECREASE';
        $adjQuantity = abs($diff);

        $this->db->transBegin();

        try {
            $this->db->table('stock_adjustments')->insert([
                'id_batch'   => $batchId,
                'type'       => $adjType,
                'quantity'   => $adjQuantity,
                'reason'     => $data['reason'] ?? 'Manual adjustment',
                'status'     => 'PENDING',
                'created_by' => $actor['id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $adjustmentId = $this->db->insertID();

            $this->db->transCommit();

            return [
                'status' => true,
                'message' => 'Penyesuaian stok dibuat dan menunggu persetujuan',
                'data' => [
                    'adjustment_id' => $adjustmentId,
                    'batch_id' => $batchId,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $newQuantity,
                    'status' => 'PENDING'
                ]
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'StockService::adjustStock: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return [
                'status' => false,
                'message' => 'Gagal membuat penyesuaian stock'
            ];
        }
    }

    public function approveAdjustment(int $id, ?int $approvedBy = null): array
    {
        $this->db->transBegin();

        try {
            $adjustment = $this->db->query(
                'SELECT * FROM stock_adjustments WHERE id_adjustment = ? FOR UPDATE',
                [$id]
            )->getRowArray();

            if (!$adjustment) {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Adjustment tidak ditemukan'
                ];
            }

            if ($adjustment['status'] !== 'PENDING') {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Hanya adjustment dengan status PENDING yang bisa disetujui'
                ];
            }

            if (empty($adjustment['id_batch'])) {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Adjustment tidak memiliki batch yang valid'
                ];
            }

            $lockResult = $this->db->query(
                'SELECT quantity FROM product_batches WHERE id_batch = ? FOR UPDATE',
                [$adjustment['id_batch']]
            );
            $batchRow = $lockResult->getRowArray();

            if (!$batchRow) {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Batch tidak ditemukan'
                ];
            }

            $currentQuantity = (int)$batchRow['quantity'];
            $delta = $adjustment['type'] === 'INCREASE'
                ? (int)$adjustment['quantity']
                : -(int)$adjustment['quantity'];

            $newQuantity = $currentQuantity + $delta;

            if ($newQuantity < 0) {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Persetujuan dibatalkan: stok hasil adjustment tidak boleh negatif'
                ];
            }

            $this->db->table('product_batches')
                ->where('id_batch', $adjustment['id_batch'])
                ->update([
                    'quantity'   => $newQuantity,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->db->table('stock_adjustments')
                ->where('id_adjustment', $id)
                ->where('status', 'PENDING')
                ->update([
                    'status'      => 'APPROVED',
                    'approved_by' => $approvedBy,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

            if ($this->db->affectedRows() !== 1) {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Status adjustment berubah saat proses persetujuan',
                    'code'    => 409,
                ];
            }

            if (!$this->db->transCommit()) {
                throw new \RuntimeException('Gagal menyimpan persetujuan adjustment');
            }

            return [
                'status' => true,
                'message' => 'Adjustment disetujui dan stok berhasil diperbarui'
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'StockService::approveAdjustment: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return [
                'status' => false,
                'message' => 'Gagal menyetujui adjustment'
            ];
        }
    }

    public function rejectAdjustment(int $id, ?int $rejectedBy = null): array
    {
        $this->db->transBegin();

        try {
            $lockResult = $this->db->query(
                'SELECT * FROM stock_adjustments WHERE id_adjustment = ? FOR UPDATE',
                [$id]
            );
            $adjustment = $lockResult->getRowArray();

            if (!$adjustment) {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Adjustment tidak ditemukan'
                ];
            }

            if ($adjustment['status'] !== 'PENDING') {
                $this->db->transRollback();
                return [
                    'status' => false,
                    'message' => 'Hanya adjustment dengan status PENDING yang bisa ditolak'
                ];
            }

            $this->db->table('stock_adjustments')
                ->where('id_adjustment', $id)
                ->update([
                    'status'      => 'REJECTED',
                    'approved_by' => $rejectedBy,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

            $this->db->transCommit();

            return [
                'status' => true,
                'message' => 'Adjustment ditolak'
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'StockService::rejectAdjustment: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return [
                'status' => false,
                'message' => 'Gagal menolak adjustment'
            ];
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
            return [
                'status' => false,
                'message' => 'Adjustment tidak ditemukan'
            ];
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
            return [
                'status' => false,
                'message' => 'Produk tidak ditemukan'
            ];
        }

        $batches = $this->db
            ->table('product_batches pb')
            ->select('
                pb.*,
                rl.rack_code AS rack_location_code
            ')
            ->join('rack_locations rl', 'rl.id_rack = pb.id_rack', 'left')
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

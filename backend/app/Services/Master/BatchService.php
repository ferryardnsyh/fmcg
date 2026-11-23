<?php

namespace App\Services\Master;

use App\Models\ProductBatchModel;
use Config\Database;

class BatchService
{
    protected ProductBatchModel $batchModel;

    public function __construct()
    {
        $this->batchModel = new ProductBatchModel();
    }

    public function getBatches(): array
    {
        $data = $this->batchModel
            ->select('product_batches.*, products.name AS product_name, rack_locations.name AS rack_name, rack_locations.rack_code, DATEDIFF(product_batches.expiry_date, NOW()) AS days_remaining')
            ->join('products', 'products.id_product = product_batches.id_product', 'left')
            ->join('rack_locations', 'rack_locations.id_rack = product_batches.id_rack', 'left')
            ->orderBy('product_batches.created_at', 'DESC')
            ->findAll();

        return ['status' => true, 'data' => $data];
    }

    public function getBatch($id): array
    {
        $data = $this->batchModel
            ->select('product_batches.*, products.name AS product_name')
            ->join('products', 'products.id_product = product_batches.id_product', 'left')
            ->where('product_batches.id_batch', $id)
            ->first();

        if (!$data) {
            return $this->fail('Batch tidak ditemukan', 404);
        }

        return ['status' => true, 'data' => $data];
    }

    public function getStatistic(): array
    {
        $db = Database::connect();

        $total = $db->table('product_batches')->where('quantity >', 0)->countAllResults();
        $expiredCount = $db->table('product_batches')
            ->where('expiry_date <', date('Y-m-d'))
            ->where('quantity >', 0)
            ->countAllResults();
        $totalQty = $db->table('product_batches')
            ->where('quantity >', 0)
            ->selectSum('quantity')
            ->get()->getRowArray();

        return [
            'status' => true,
            'data' => [
                'total'        => $total,
                'available'    => $total - $expiredCount,
                'expired'      => $expiredCount,
                'total_jumlah' => (int)($totalQty['quantity'] ?? 0),
            ],
        ];
    }

    public function mutationDisabled(): array
    {
        return $this->fail(
            'Mutasi data batch tidak tersedia. Gunakan alur inbound/outbound/penyesuaian stok.',
            410
        );
    }

    public function deleteBatch($id): array
    {
        $existing = $this->batchModel->find($id);
        if (!$existing) {
            return $this->fail('Batch tidak ditemukan', 404);
        }

        $db = Database::connect();

        $inUse = $db->table('inbound_details')->where('id_batch', $id)->countAllResults()
            + $db->table('outbound_details')->where('id_batch', $id)->countAllResults()
            + $db->table('stock_opname_details')->where('id_batch', $id)->countAllResults()
            + $db->table('stock_adjustments')->where('id_batch', $id)->countAllResults();

        if ($inUse > 0) {
            return $this->fail(
                'Batch tidak bisa dihapus karena masih digunakan di transaksi inbound/outbound/opname/adjustment',
                409
            );
        }

        if ((int) ($existing['quantity'] ?? 0) !== 0) {
            return $this->fail(
                'Batch tidak bisa dihapus karena masih memiliki stok. Gunakan penyesuaian stok.',
                409
            );
        }

        $this->batchModel->delete($id);

        return [
            'status' => true,
            'message' => 'Batch berhasil dihapus',
        ];
    }

    private function fail(string $message, int $code): array
    {
        return [
            'status' => false,
            'message' => $message,
            'code' => $code,
        ];
    }
}

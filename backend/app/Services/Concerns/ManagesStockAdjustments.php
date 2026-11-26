<?php

namespace App\Services\Concerns;

use App\Support\ValueRules;
use App\Support\WmsPolicy;
use App\Validation\MasterInput;
use RuntimeException;

trait ManagesStockAdjustments
{
    private function prepareAdjustmentInput(array $data): array
    {
        $data['reason'] = isset($data['reason']) ? trim(strip_tags((string)$data['reason'])) : null;
        if ($data['reason'] !== null && mb_strlen($data['reason']) > 200) {
            return [null, $this->makeFailureResult('Alasan adjustment maksimal 200 karakter')];
        }

        if (empty($data['product_id']) && empty($data['batch_id'])) {
            return [null, $this->makeFailureResult('Product ID wajib diisi')];
        }

        if (isset($data['type']) || isset($data['quantity'])) {
            $rawType = $data['type'] ?? '';
            if (!in_array($rawType, [WmsPolicy::TYPE_INCREASE, WmsPolicy::TYPE_DECREASE, WmsPolicy::TYPE_IN, WmsPolicy::TYPE_OUT], false)) {
                return [null, $this->makeFailureResult('Tipe adjustment harus INCREASE atau DECREASE')];
            }
            $type = in_array($rawType, [WmsPolicy::TYPE_INCREASE, WmsPolicy::TYPE_IN], false)
                ? WmsPolicy::TYPE_INCREASE
                : WmsPolicy::TYPE_DECREASE;

            $intQty = ValueRules::positiveInt($data['quantity'] ?? null, 2147483647);
            if ($intQty === null) {
                return [null, $this->makeFailureResult('Quantity adjustment wajib diisi berupa bilangan bulat antara 1 dan 2147483647')];
            }

            if (empty($data['reason']) || trim((string)$data['reason']) === '') {
                return [null, $this->makeFailureResult('Alasan adjustment wajib diisi')];
            }
        }

        $batchId = ValueRules::positiveId($data['batch_id'] ?? null);
        if (($data['batch_id'] ?? null) !== null && $batchId === null) {
            return [null, $this->makeFailureResult('Batch tidak valid')];
        }
        if (($data['product_id'] ?? null) !== null && ValueRules::positiveId($data['product_id']) === null) {
            return [null, $this->makeFailureResult('Product ID tidak valid')];
        }

        if (empty($batchId)) {
            $batch = $this->db->table('product_batches')
                ->where('id_product', $data['product_id'])
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getRowArray();

            if (!$batch) {
                return [null, $this->makeFailureResult('Tidak ditemukan batch untuk produk ini')];
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
                return [null, $this->makeFailureResult('Batch tidak ditemukan')];
            }
            $data['product_id'] = $batch['id_product'];
            $quantityBefore = (int)$batch['quantity'];
        }

        if (isset($data['new_quantity'])) {
            $newQuantity = MasterInput::parseInteger($data['new_quantity'], 0, 2147483647);
            if ($newQuantity === null) {
                return [null, $this->makeFailureResult('Quantity baru harus bilangan bulat antara 0 dan 2147483647')];
            }
        } elseif (!empty($data['type']) && isset($data['quantity'])) {
            $delta = $intQty;
            if ($type === WmsPolicy::TYPE_DECREASE) {
                $newQuantity = $quantityBefore - $delta;
            } else {
                $newQuantity = $quantityBefore + $delta;
            }
        } else {
            return [null, $this->makeFailureResult('Product ID dan quantity baru wajib diisi')];
        }

        $diff = $newQuantity - $quantityBefore;

        if ($newQuantity < 0) {
            return [null, $this->makeFailureResult('Quantity tidak boleh negatif')];
        }

        if ($diff === 0) {
            return [null, $this->makeFailureResult('Quantity tidak berubah')];
        }

        $adjType = $diff >= 0 ? WmsPolicy::TYPE_INCREASE : WmsPolicy::TYPE_DECREASE;
        $adjQuantity = abs($diff);

        return [[
            'data'            => $data,
            'batch_id'        => $batchId,
            'quantity_before' => $quantityBefore,
            'new_quantity'    => $newQuantity,
            'adj_type'        => $adjType,
            'adj_quantity'    => $adjQuantity,
        ], null];
    }

    private function insertPendingAdjustment(array $payload, ?array $actor): array
    {
        $this->db->table('stock_adjustments')->insert([
            'id_batch'   => $payload['batch_id'],
            'type'       => $payload['adj_type'],
            'quantity'   => $payload['adj_quantity'],
            'reason'     => $payload['data']['reason'] ?? 'Manual adjustment',
            'status'     => WmsPolicy::STATUS_PENDING,
            'created_by' => $actor['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $adjustmentId = $this->db->insertID();

        return $this->makeSuccessResult('Penyesuaian stok dibuat dan menunggu persetujuan', null, [
            'adjustment_id'   => $adjustmentId,
            'batch_id'        => $payload['batch_id'],
            'quantity_before' => $payload['quantity_before'],
            'quantity_after'  => $payload['new_quantity'],
            'status'          => WmsPolicy::STATUS_PENDING,
        ]);
    }

    private function rollbackWith(array $result): array
    {
        $this->db->transRollback();

        return $result;
    }

    private function lockAdjustmentForDecision(int $id, string $statusMessage, bool $requireBatch): array
    {
        $adjustment = $this->db->query(
            'SELECT * FROM stock_adjustments WHERE id_adjustment = ? FOR UPDATE',
            [$id]
        )->getRowArray();

        if (!$adjustment) {
            return [null, $this->rollbackWith($this->makeFailureResult('Adjustment tidak ditemukan'))];
        }

        if ($adjustment['status'] !== WmsPolicy::STATUS_PENDING) {
            return [null, $this->rollbackWith($this->makeFailureResult($statusMessage))];
        }

        if ($requireBatch && empty($adjustment['id_batch'])) {
            return [null, $this->rollbackWith($this->makeFailureResult('Adjustment tidak memiliki batch yang valid'))];
        }

        return [$adjustment, null];
    }

    private function lockAdjustmentBatch($batchId): array
    {
        $lockResult = $this->db->query(
            'SELECT quantity FROM product_batches WHERE id_batch = ? FOR UPDATE',
            [$batchId]
        );
        $batchRow = $lockResult->getRowArray();

        if (!$batchRow) {
            return [null, $this->rollbackWith($this->makeFailureResult('Batch tidak ditemukan'))];
        }

        return [$batchRow, null];
    }

    private function applyApprovedAdjustment(int $id, ?int $approvedBy, array $adjustment, array $batchRow): ?array
    {
        $currentQuantity = (int)$batchRow['quantity'];
        $delta = $adjustment['type'] === WmsPolicy::TYPE_INCREASE
            ? (int)$adjustment['quantity']
            : -(int)$adjustment['quantity'];

        $newQuantity = $currentQuantity + $delta;

        if ($newQuantity < 0) {
            return $this->rollbackWith($this->makeFailureResult('Persetujuan dibatalkan: stok hasil adjustment tidak boleh negatif'));
        }

        $this->db->table('product_batches')
            ->where('id_batch', $adjustment['id_batch'])
            ->update([
                'quantity'   => $newQuantity,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->db->table('stock_adjustments')
            ->where('id_adjustment', $id)
            ->where('status', WmsPolicy::STATUS_PENDING)
            ->update([
                'status'      => WmsPolicy::STATUS_APPROVED,
                'approved_by' => $approvedBy,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        if ($this->db->affectedRows() !== 1) {
            return $this->rollbackWith($this->makeFailureResult('Status adjustment berubah saat proses persetujuan', 409));
        }

        if (!$this->db->transCommit()) {
            throw new RuntimeException('Gagal menyimpan persetujuan adjustment');
        }

        return $this->makeSuccessResult('Adjustment disetujui dan stok berhasil diperbarui');
    }

    private function rejectAdjustmentRecord(int $id, ?int $rejectedBy): array
    {
        $this->db->table('stock_adjustments')
            ->where('id_adjustment', $id)
            ->update([
                'status'      => WmsPolicy::STATUS_REJECTED,
                'approved_by' => $rejectedBy,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        $this->db->transCommit();

        return $this->makeSuccessResult('Adjustment ditolak');
    }
}

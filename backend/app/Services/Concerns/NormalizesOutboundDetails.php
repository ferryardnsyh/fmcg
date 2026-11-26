<?php

namespace App\Services\Concerns;

trait NormalizesOutboundDetails
{
    private function normalizeDetails(array $details): array
    {
        if ($details === []) {
            return [null, 'Detail barang wajib diisi'];
        }

        $productIds = [];
        $batchIds = [];
        $parsed = [];
        $errorIndex = null;
        $errorMessage = null;

        foreach ($details as $index => $detail) {
            $position = $index + 1;

            if (!is_array($detail)) {
                $errorIndex = $index;
                $errorMessage = 'Format detail barang ke-' . $position . ' tidak valid';
                break;
            }

            $productId = $this->parsePositiveInt($detail['id_product'] ?? null);
            if ($productId === null) {
                $errorIndex = $index;
                $errorMessage = 'Produk pada detail ke-' . $position . ' tidak valid';
                break;
            }

            $batchId = $this->parsePositiveInt($detail['id_batch'] ?? null);
            if ($batchId === null) {
                $errorIndex = $index;
                $errorMessage = 'Batch pada detail ke-' . $position . ' tidak valid';
                break;
            }

            $quantity = $this->parsePositiveInt($detail['quantity'] ?? null, self::MAX_QUANTITY);
            if ($quantity === null) {
                $errorIndex = $index;
                $errorMessage = 'Quantity pada detail ke-' . $position . ' harus bilangan bulat positif';
                break;
            }

            $parsed[$index] = [
                'id_product' => $productId,
                'id_batch'   => $batchId,
                'quantity'   => $quantity,
            ];
            $productIds[$productId] = $productId;
            $batchIds[$batchId] = $batchId;
        }

        $products = [];
        $batches = [];

        if ($productIds !== []) {
            foreach ($this->db
                ->table('products')
                ->select('id_product')
                ->whereIn('id_product', $productIds)
                ->get()
                ->getResultArray() as $product) {
                $products[(int)$product['id_product']] = $product;
            }

            foreach ($this->db
                ->table('product_batches pb')
                ->select('pb.id_batch, pb.id_product, pb.batch_number, pb.quantity')
                ->join('products p', 'p.id_product = pb.id_product', 'inner')
                ->whereIn('pb.id_batch', $batchIds)
                ->whereIn('pb.id_product', $productIds)
                ->get()
                ->getResultArray() as $batch) {
                $batches[(int)$batch['id_batch']] = $batch;
            }
        }

        $normalized = [];

        foreach (array_keys($details) as $index) {
            if ($index === $errorIndex) {
                return [null, $errorMessage];
            }

            $candidate = $parsed[$index];

            if (!isset($products[$candidate['id_product']])) {
                return [null, 'Produk pada detail ke-' . ($index + 1) . ' tidak ditemukan'];
            }

            $batch = $batches[$candidate['id_batch']] ?? null;
            if (!$batch || (int)$batch['id_product'] !== $candidate['id_product']) {
                return [null, 'Batch pada detail ke-' . ($index + 1) . ' tidak sesuai dengan produk yang dipilih'];
            }

            if ((int)$batch['quantity'] <= 0) {
                return [null, 'Batch ' . ($batch['batch_number'] ?? $candidate['id_batch']) . ' tidak tersedia'];
            }

            $normalized[] = $candidate;
        }

        return [$normalized, null];
    }

    private function getLockedBatch(int $id): ?array
    {
        $batch = $this->db
            ->query('SELECT id_batch, id_product, batch_number, quantity FROM product_batches WHERE id_batch = ? FOR UPDATE', [$id])
            ->getRowArray();

        return $batch ?: null;
    }

    private function insertOutboundDetails(int $idOutbound, array $details): void
    {
        foreach ($details as $detail) {
            $this->db->table('outbound_details')->insert([
                'id_outbound' => $idOutbound,
                'id_batch'    => $detail['id_batch'],
                'quantity'    => $detail['quantity'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function normalizeCompleteDetails(int $idOutbound): array
    {
        $details = $this->db
            ->table('outbound_details')
            ->where('id_outbound', $idOutbound)
            ->get()
            ->getResultArray();

        if ($details === []) {
            return [null, $this->fail('Detail outbound kosong', 400)];
        }

        $requirements = [];

        foreach ($details as $detail) {
            $batchId = $this->parsePositiveInt($detail['id_batch'] ?? null);
            $quantity = $this->parsePositiveInt($detail['quantity'] ?? null, self::MAX_QUANTITY);

            if ($batchId === null || $quantity === null) {
                return [null, $this->fail('Detail outbound tidak valid', 400)];
            }

            $current = $requirements[$batchId] ?? 0;
            if ($current + $quantity > self::MAX_QUANTITY) {
                return [null, $this->fail('Total quantity melebihi batas yang diizinkan', 400)];
            }

            $requirements[$batchId] = $current + $quantity;
        }

        return [$requirements, null];
    }

    private function lockCompleteBatches(array $requirements): array
    {
        $lockedBatches = [];

        foreach ($requirements as $batchId => $required) {
            $batch = $this->getLockedBatch((int)$batchId);
            $batchLabel = $batch ? ($batch['batch_number'] ?? $batchId) : $batchId;

            if (!$batch) {
                return [null, $this->fail('Batch ' . $batchLabel . ' tidak ditemukan', 409)];
            }

            if (empty($batch['id_product'])) {
                return [null, $this->fail('Batch ' . $batchLabel . ' tidak memiliki produk yang valid', 409)];
            }

            $available = (int)$batch['quantity'];
            if ($available <= 0) {
                return [null, $this->fail('Batch ' . $batchLabel . ' tidak tersedia', 409)];
            }

            if ($available < $required) {
                return [null, $this->fail(
                    'Stok tidak mencukupi untuk batch ' . $batchLabel . '. Stok tersedia: ' . $available . ', diminta: ' . $required,
                    409
                )];
            }

            $lockedBatches[$batchId] = [
                'label'    => $batchLabel,
                'required' => $required,
            ];
        }

        return [$lockedBatches, null];
    }

    private function applyCompleteStockMutation(array $lockedBatches, string $now): ?array
    {
        foreach ($lockedBatches as $batchId => $batchData) {
            $this->db->query(
                'UPDATE product_batches SET quantity = quantity - ?, updated_at = ? WHERE id_batch = ? AND quantity >= ?',
                [$batchData['required'], $now, $batchId, $batchData['required']]
            );
            $affected = $this->db->affectedRows();

            if ($affected !== 1) {
                return $this->fail('Stok batch ' . $batchData['label'] . ' berubah saat proses complete', 409);
            }
        }

        return null;
    }

    private function finalizeCompleteStatus(int $idOutbound, string $now): ?array
    {
        $this->db
            ->table('outbound')
            ->where('id_outbound', $idOutbound)
            ->where('status', 'DRAFT')
            ->update([
                'status'     => 'COMPLETED',
                'updated_at' => $now,
            ]);
        $updated = $this->db->affectedRows();

        if ($updated !== 1) {
            return $this->fail('Status outbound berubah saat proses complete', 409);
        }

        return null;
    }
}

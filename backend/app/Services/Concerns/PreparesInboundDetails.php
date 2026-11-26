<?php

namespace App\Services\Concerns;

trait PreparesInboundDetails
{
    private function prepareDetails(array $details, int $supplierId, bool $includeIds = false): array
    {
        if (empty($details)) {
            return [[], $this->fail('Detail barang wajib diisi')];
        }

        $prepared = [];
        $seen = [];
        $detailIds = [];

        foreach ($details as $idx => $detail) {
            $row = $idx + 1;
            if (!is_array($detail)) {
                return [[], $this->fail('Baris ' . $row . ': Detail tidak valid')];
            }

            $detailId = null;
            if ($includeIds && array_key_exists('id_inbound_detail', $detail) && $detail['id_inbound_detail'] !== null) {
                $detailId = $this->normalizeId($detail['id_inbound_detail']);
                if ($detailId === null || isset($detailIds[$detailId])) {
                    return [[], $this->fail('Detail barang tidak valid')];
                }
                $detailIds[$detailId] = true;
            }

            $productId = $this->normalizeId($detail['id_product'] ?? null);
            if (!$productId) {
                return [[], $this->fail('Baris ' . $row . ': Produk wajib dipilih')];
            }

            [$batchNumber, $error] = $this->sanitizeDocumentText(
                $detail['batch_number'] ?? null,
                self::BATCH_NUMBER_LENGTH,
                'Baris ' . $row . ': Batch/Lot',
                true
            );
            if ($error !== null) {
                return [[], $error];
            }

            [$quantity, $error] = $this->parseQuantity($detail['quantity'] ?? null, $row);
            if ($error !== null) {
                return [[], $error];
            }

            [$expiryDate, $error] = $this->validateDate($detail['expiry_date'] ?? null, 'Baris ' . $row . ': Tanggal expired');
            if ($error !== null) {
                return [[], $error];
            }

            [$rackId, $error] = $this->validateRack($detail['id_rack'] ?? null, $row);
            if ($error !== null) {
                return [[], $error];
            }

            $key = $productId . '|' . strtoupper($batchNumber);
            if (isset($seen[$key])) {
                return [[], $this->fail('Baris ' . $row . ': Duplikat batch "' . $batchNumber . '" untuk produk yang sama')];
            }
            $seen[$key] = true;

            $item = [
                'id_product'   => $productId,
                'batch_number' => $batchNumber,
                'quantity'     => $quantity,
                'expiry_date'  => $expiryDate,
                'id_rack'      => $rackId,
            ];
            if ($includeIds) {
                $item['id_inbound_detail'] = $detailId;
            }
            $prepared[] = $item;
        }

        $productIds = array_column($prepared, 'id_product');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $validProducts = $this->db
            ->query(
                "SELECT id_product FROM products WHERE id_product IN ($placeholders) AND id_supplier = ? AND status = 'AKTIF'",
                array_merge($productIds, [$supplierId])
            )
            ->getResultArray();
        $validIds = array_map('intval', array_column($validProducts, 'id_product'));

        foreach ($prepared as $idx => $item) {
            if (!in_array($item['id_product'], $validIds, true)) {
                return [[], $this->fail('Baris ' . ($idx + 1) . ': Produk tidak ditemukan, tidak aktif, atau tidak sesuai dengan supplier yang dipilih')];
            }
        }

        return [$prepared, null];
    }

    private function inboundDetailValues(array $detail, string $idInbound, bool $includeCreatedAt = false, bool $preserveBatch = false): array
    {
        $values = [
            'id_inbound'   => $idInbound,
            'id_product'   => $detail['id_product'],
            'batch_number' => $detail['batch_number'],
            'quantity'     => $detail['quantity'],
            'expiry_date'  => $detail['expiry_date'],
            'id_rack'      => $detail['id_rack'],
        ];

        if (!$preserveBatch) {
            $values['id_batch'] = null;
        }

        if ($includeCreatedAt) {
            $values['created_at'] = date('Y-m-d H:i:s');
        }

        return $values;
    }

    private function insertInboundDetails($idInbound, array $details): void
    {
        foreach ($details as $detail) {
            $this->db->table('inbound_details')->insert($this->inboundDetailValues($detail, (string) $idInbound, true));
        }
    }

    private function syncInboundDetails(string $idInbound, array $details): ?array
    {
        if (!$this->db->fieldExists('id_inbound_detail', 'inbound_details')) {
            $this->db->table('inbound_details')->where('id_inbound', $idInbound)->delete();
            $this->insertInboundDetails($idInbound, $details);
            return null;
        }

        $providedIds = [];
        foreach ($details as $detail) {
            if (($detail['id_inbound_detail'] ?? null) !== null) {
                $providedIds[] = (int) $detail['id_inbound_detail'];
            }
        }

        $existingIds = array_map('intval', array_column(
            $this->db->table('inbound_details')
                ->select('id_inbound_detail')
                ->where('id_inbound', $idInbound)
                ->get()
                ->getResultArray(),
            'id_inbound_detail'
        ));

        foreach ($providedIds as $detailId) {
            if (!in_array($detailId, $existingIds, true)) {
                return $this->fail('Detail barang tidak valid');
            }
        }

        if ($providedIds !== []) {
            $this->db->table('inbound_details')
                ->where('id_inbound', $idInbound)
                ->whereNotIn('id_inbound_detail', $providedIds)
                ->delete();
        } else {
            $this->db->table('inbound_details')->where('id_inbound', $idInbound)->delete();
        }

        foreach ($details as $detail) {
            $detailId = $detail['id_inbound_detail'] ?? null;
            $values = $this->inboundDetailValues($detail, $idInbound, false, $detailId !== null);

            if ($detailId !== null) {
                $this->db->table('inbound_details')
                    ->where('id_inbound', $idInbound)
                    ->where('id_inbound_detail', (int) $detailId)
                    ->update($values);
                continue;
            }

            $values['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('inbound_details')->insert($values);
        }

        return null;
    }

    private function validateCompletionHeader(string $id): array
    {
        $header = $this->db->query(
            "SELECT ih.*, s.name AS supplier_name FROM inbound ih LEFT JOIN suppliers s ON s.id_supplier = ih.id_supplier WHERE ih.id_inbound = ? FOR UPDATE",
            [$id]
        )->getRowArray();

        if (!$header) {
            return [null, $this->fail('Inbound tidak ditemukan', 404)];
        }

        if ($header['status'] !== 'DRAFT') {
            return [null, $this->fail('Hanya inbound dengan status DRAFT yang bisa diselesaikan', 409)];
        }

        $supplierId = $this->normalizeId($header['id_supplier']);
        if (!$supplierId) {
            return [null, $this->fail('Supplier pada inbound tidak valid')];
        }

        return [$supplierId, null];
    }

    private function getLockedExistingBatch(int $productId, string $batchNumber, string $expiryDate, int $rackId, int $quantity, int $row): array
    {
        $existingBatch = $this->db->query(
            "SELECT * FROM product_batches WHERE id_product = ? AND batch_number = ? FOR UPDATE",
            [$productId, $batchNumber]
        )->getRowArray();

        if (!$existingBatch) {
            return [null, null];
        }

        if ($this->normalizeId($existingBatch['id_product']) !== $productId) {
            return [null, $this->fail('Baris ' . $row . ': Batch "' . $batchNumber . '" terhubung dengan produk yang berbeda', 409)];
        }

        if (!empty($existingBatch['expiry_date']) && $existingBatch['expiry_date'] !== $expiryDate) {
            return [null, $this->fail('Baris ' . $row . ': Tanggal expired tidak cocok dengan batch "' . $batchNumber . '" yang sudah ada', 409)];
        }

        $existingRackId = $this->normalizeId($existingBatch['id_rack'] ?? null);
        if ($existingRackId && $existingRackId !== $rackId) {
            return [null, $this->fail('Baris ' . $row . ': Rak tidak cocok dengan batch "' . $batchNumber . '" yang sudah ada', 409)];
        }

        if ((int) $existingBatch['quantity'] + $quantity > self::MAX_INT_COLUMN) {
            return [null, $this->fail('Baris ' . $row . ': Total stok batch melebihi batas yang diizinkan')];
        }

        return [$existingBatch, null];
    }

    private function prepareCompletionDetails(string $id, int $headerSupplierId): array
    {
        $detailRows = $this->db->query(
            "SELECT id.*, p.id_supplier AS product_supplier_id, p.name AS product_name, p.status AS product_status
             FROM inbound_details id
             LEFT JOIN products p ON p.id_product = id.id_product
             WHERE id.id_inbound = ?
             FOR UPDATE",
            [$id]
        )->getResultArray();

        if (empty($detailRows)) {
            return [[], $this->fail('Tidak ada detail barang untuk inbound ini')];
        }

        $prepared = [];
        $seen = [];

        foreach ($detailRows as $idx => $detail) {
            $row = $idx + 1;

            if (!$this->normalizeId($detail['id_inbound_detail'])) {
                return [[], $this->fail('Detail inbound tidak valid')];
            }

            if (!empty($detail['id_batch'])) {
                return [[], $this->fail('Detail baris ' . $row . ' sudah terhubung dengan batch. Inbound tidak dapat diselesaikan dua kali.', 409)];
            }

            $productId = $this->normalizeId($detail['id_product']);
            $productSupplierId = $this->normalizeId($detail['product_supplier_id'] ?? null);
            if (!$productId || !$productSupplierId || $productSupplierId !== $headerSupplierId || ($detail['product_status'] ?? null) !== 'AKTIF') {
                return [[], $this->fail('Baris ' . $row . ': Produk tidak ditemukan, tidak aktif, atau tidak sesuai dengan supplier yang dipilih')];
            }

            [$batchNumber, $error] = $this->sanitizeDocumentText($detail['batch_number'] ?? null, self::BATCH_NUMBER_LENGTH, 'Baris ' . $row . ': Batch/Lot', true);
            if ($error !== null) {
                return [[], $error];
            }

            [$quantity, $error] = $this->parseQuantity($detail['quantity'] ?? null, $row);
            if ($error !== null) {
                return [[], $error];
            }

            [$expiryDate, $error] = $this->validateDate($detail['expiry_date'] ?? null, 'Baris ' . $row . ': Tanggal expired');
            if ($error !== null) {
                return [[], $error];
            }

            [$rackId, $error] = $this->validateRack($detail['id_rack'] ?? null, $row);
            if ($error !== null) {
                return [[], $error];
            }

            $key = $productId . '|' . strtoupper($batchNumber);
            if (isset($seen[$key])) {
                return [[], $this->fail('Baris ' . $row . ': Duplikat batch "' . $batchNumber . '" untuk produk yang sama')];
            }
            $seen[$key] = true;

            [$existingBatch, $error] = $this->getLockedExistingBatch($productId, $batchNumber, $expiryDate, $rackId, $quantity, $row);
            if ($error !== null) {
                return [[], $error];
            }

            $prepared[] = [
                'id_inbound_detail' => (int) $detail['id_inbound_detail'],
                'id_product'        => $productId,
                'batch_number'      => $batchNumber,
                'quantity'          => $quantity,
                'expiry_date'       => $expiryDate,
                'id_rack'           => $rackId,
                'existing_batch'    => $existingBatch,
            ];
        }

        return [$prepared, null];
    }

    private function applyStockMutations(array $prepared): void
    {
        foreach ($prepared as $detail) {
            $existingBatch = $detail['existing_batch'];

            if ($existingBatch) {
                $batchId = (int) $existingBatch['id_batch'];
                $this->db->table('product_batches')
                    ->where('id_batch', $batchId)
                    ->update([
                        'quantity'    => (int) $existingBatch['quantity'] + $detail['quantity'],
                        'expiry_date' => $existingBatch['expiry_date'] ?: $detail['expiry_date'],
                        'id_rack'     => $this->normalizeId($existingBatch['id_rack'] ?? null) ?: $detail['id_rack'],
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
            } else {
                $this->db->table('product_batches')->insert([
                    'id_product'   => $detail['id_product'],
                    'batch_number' => $detail['batch_number'],
                    'expiry_date'  => $detail['expiry_date'],
                    'quantity'     => $detail['quantity'],
                    'id_rack'      => $detail['id_rack'],
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                $batchId = $this->db->insertID();
            }

            $this->db->table('inbound_details')
                ->where('id_inbound_detail', $detail['id_inbound_detail'])
                ->update(['id_batch' => $batchId]);
        }
    }

    private function finalizeInboundHeader(string $id): void
    {
        $this->db->table('inbound')
            ->where('id_inbound', $id)
            ->update([
                'status'     => 'COMPLETED',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}

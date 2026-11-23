<?php

namespace App\Services;

use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

class InboundService
{
    protected $db;

    private const MAX_INT_COLUMN = 2147483647;
    private const DOCUMENT_NUMBER_LENGTH = 50;
    private const BATCH_NUMBER_LENGTH = 100;
    private const ALLOWED_ROLES = ['SUPERADMIN', 'ADMIN', 'GUDANG'];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function fail(string $message, int $code = 400): array
    {
        return [
            'status'  => false,
            'message' => $message,
            'code'    => $code,
        ];
    }

    private function runInTransaction(callable $operation, string $logContext, string $failureMessage): array
    {
        $this->db->transBegin();

        try {
            $result = $operation();

            if (is_array($result) && ($result['status'] ?? null) === false) {
                $this->db->transRollback();
                return $result;
            }

            if (!$this->db->transCommit()) {
                throw new \RuntimeException('Gagal menyimpan transaksi');
            }

            return $result;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', $logContext . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->fail($failureMessage, 500);
        }
    }

    private function validateActor(?array $actor): array
    {
        if ($actor === null) {
            return [null, ['status' => false, 'code' => 401, 'message' => 'Unauthorized. Silakan login terlebih dahulu.']];
        }

        $id = $this->normalizeId($actor['id'] ?? null);
        if ($id === null) {
            return [null, ['status' => false, 'code' => 401, 'message' => 'Unauthorized. Silakan login terlebih dahulu.']];
        }

        $role = strtoupper(trim((string)($actor['role'] ?? '')));
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            return [null, ['status' => false, 'code' => 403, 'message' => 'Forbidden. Anda tidak memiliki akses ke transaksi inbound.']];
        }

        return [['id' => $id, 'role' => $role], null];
    }

    private function normalizeId($value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if ($value === null || (!is_string($value) && !is_numeric($value))) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d+$/', $value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function parseQuantity($value, int $row): array
    {
        if (is_int($value)) {
            $quantity = $value;
        } else {
            if ($value === null || (!is_string($value) && !is_numeric($value))) {
                return [null, $this->fail('Baris ' . $row . ': Jumlah harus berupa bilangan bulat positif')];
            }

            $raw = trim((string) $value);
            if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
                return [null, $this->fail('Baris ' . $row . ': Jumlah harus berupa bilangan bulat positif')];
            }

            $quantity = filter_var($raw, FILTER_VALIDATE_INT);
            if ($quantity === false) {
                return [null, $this->fail('Baris ' . $row . ': Jumlah tidak valid')];
            }
        }

        if ($quantity < 1 || $quantity > self::MAX_INT_COLUMN) {
            return [null, $this->fail('Baris ' . $row . ': Jumlah harus antara 1 dan ' . self::MAX_INT_COLUMN)];
        }

        return [$quantity, null];
    }

    private function sanitizeText($value, int $maxLength, string $label, bool $required): array
    {
        if ($value === null) {
            if ($required) {
                return [null, $this->fail($label . ' wajib diisi')];
            }
            return [null, null];
        }

        if (!is_string($value) && !is_numeric($value)) {
            return [null, $this->fail($label . ' tidak valid')];
        }

        $value = trim(strip_tags((string) $value));
        if ($value === '') {
            if ($required) {
                return [null, $this->fail($label . ' wajib diisi')];
            }
            return [null, null];
        }

        if (mb_strlen($value) > $maxLength) {
            return [null, $this->fail($label . ' maksimal ' . $maxLength . ' karakter')];
        }

        return [$value, null];
    }

    private function validateDate($value, string $label): array
    {
        if ($value === null || !is_string($value)) {
            return [null, $this->fail($label . ' wajib diisi')];
        }

        $value = trim($value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return [null, $this->fail($label . ' tidak valid')];
        }

        if (!checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return [null, $this->fail($label . ' tidak valid')];
        }

        return [$value, null];
    }

    private function validateSupplier($value, bool $required = true): array
    {
        if ($value === null && !$required) {
            return [null, null];
        }

        $supplierId = $this->normalizeId($value);
        if (!$supplierId) {
            return [null, $this->fail('Supplier wajib dipilih')];
        }

        $exists = $this->db->table('suppliers')
            ->where('id_supplier', $supplierId)
            ->where('status', 'AKTIF')
            ->countAllResults();

        if (!$exists) {
            return [null, $this->fail('Supplier tidak ditemukan atau tidak aktif')];
        }

        return [$supplierId, null];
    }

    private function validateRack($value, int $row): array
    {
        $rackId = $this->normalizeId($value);
        if (!$rackId) {
            return [null, $this->fail('Baris ' . $row . ': Rak wajib dipilih')];
        }

        $exists = $this->db->table('rack_locations')
            ->where('id_rack', $rackId)
            ->where('status', 'AKTIF')
            ->countAllResults();

        if (!$exists) {
            return [null, $this->fail('Baris ' . $row . ': Rak tidak ditemukan atau tidak aktif')];
        }

        return [$rackId, null];
    }

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

            [$batchNumber, $error] = $this->sanitizeText(
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

    private function validateCreateHeader(array $data): array
    {
        [$receptionDate, $error] = $this->validateDate($data['reception_date'] ?? null, 'Tanggal penerimaan');
        if ($error !== null) {
            return [[], $error];
        }

        [$supplierId, $error] = $this->validateSupplier($data['id_supplier'] ?? null);
        if ($error !== null) {
            return [[], $error];
        }

        [$poNumber, $error] = $this->sanitizeText($data['po_number'] ?? null, self::DOCUMENT_NUMBER_LENGTH, 'Nomor PO', true);
        if ($error !== null) {
            return [[], $error];
        }

        [$deliveryNoteNumber, $error] = $this->sanitizeText($data['delivery_note_number'] ?? null, self::DOCUMENT_NUMBER_LENGTH, 'Nomor Surat Jalan', true);
        if ($error !== null) {
            return [[], $error];
        }

        [$notes, $error] = $this->sanitizeText($data['notes'] ?? null, 65535, 'Catatan', false);
        if ($error !== null) {
            return [[], $error];
        }

        return [[
            'reception_date'       => $receptionDate,
            'id_supplier'          => $supplierId,
            'po_number'            => $poNumber,
            'delivery_note_number' => $deliveryNoteNumber,
            'notes'                => $notes,
        ], null];
    }

    private function getDraftHeader(string $id, string $statusMessage): array
    {
        $header = $this->db->table('inbound_headers')->where('id_inbound', $id)->get()->getRowArray();
        if (!$header) {
            return [null, $this->fail('Inbound tidak ditemukan', 404)];
        }

        if ($header['status'] !== 'DRAFT') {
            return [null, $this->fail($statusMessage, 409)];
        }

        return [$header, null];
    }

    private function validateUpdatePayload(array $header, array $data): array
    {
        if (array_key_exists('id_supplier', $data)) {
            [$supplierId, $error] = $this->validateSupplier($data['id_supplier']);
        } else {
            $supplierId = $this->normalizeId($header['id_supplier']);
            $error = $supplierId ? null : $this->fail('Supplier tidak ditemukan atau tidak aktif');
        }
        if ($error !== null) {
            return [[], null, $error];
        }

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        if (array_key_exists('po_number', $data)) {
            [$value, $error] = $this->sanitizeText($data['po_number'], self::DOCUMENT_NUMBER_LENGTH, 'Nomor PO', false);
            if ($error !== null) {
                return [[], null, $error];
            }
            $updateData['po_number'] = $value;
        }
        if (array_key_exists('delivery_note_number', $data)) {
            [$value, $error] = $this->sanitizeText($data['delivery_note_number'], self::DOCUMENT_NUMBER_LENGTH, 'Nomor Surat Jalan', false);
            if ($error !== null) {
                return [[], null, $error];
            }
            $updateData['delivery_note_number'] = $value;
        }
        if (array_key_exists('id_supplier', $data)) {
            $updateData['id_supplier'] = $supplierId;
        }
        if (array_key_exists('reception_date', $data)) {
            [$value, $error] = $this->validateDate($data['reception_date'], 'Tanggal penerimaan');
            if ($error !== null) {
                return [[], null, $error];
            }
            $updateData['reception_date'] = $value;
        }
        if (array_key_exists('notes', $data)) {
            [$value, $error] = $this->sanitizeText($data['notes'], 65535, 'Catatan', false);
            if ($error !== null) {
                return [[], null, $error];
            }
            $updateData['notes'] = $value;
        }

        $details = null;
        if (array_key_exists('details', $data)) {
            if (!is_array($data['details'])) {
                return [[], null, $this->fail('Detail barang tidak valid')];
            }
            [$details, $error] = $this->prepareDetails($data['details'], $supplierId, true);
            if ($error !== null) {
                return [[], null, $error];
            }
        }

        return [$updateData, $details, null];
    }

    private function generateReceptionNumber(): string
    {
        do {
            $number = 'INB-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $this->db->table('inbound_headers')->where('reception_number', $number)->countAllResults() > 0;
        } while ($exists);

        return $number;
    }

    private function isDuplicateKeyError(DatabaseException $e): bool
    {
        return (string) $e->getCode() === '1062' || stripos($e->getMessage(), 'duplicate entry') !== false;
    }

    private function insertInboundHeader(array $header, int $actorId): array
    {
        $idInbound = null;
        $receptionNumber = null;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $receptionNumber = $this->generateReceptionNumber();

            try {
                $this->db->table('inbound_headers')->insert([
                    'reception_number'     => $receptionNumber,
                    'po_number'            => $header['po_number'],
                    'delivery_note_number' => $header['delivery_note_number'],
                    'id_supplier'          => $header['id_supplier'],
                    'reception_date'       => $header['reception_date'],
                    'notes'                => $header['notes'],
                    'status'               => 'DRAFT',
                    'created_by'           => $actorId,
                    'created_at'           => date('Y-m-d H:i:s'),
                ]);
                $idInbound = $this->db->insertID();
                break;
            } catch (DatabaseException $e) {
                if (!$this->isDuplicateKeyError($e) || $attempt === 4) {
                    throw $e;
                }
            }
        }

        if (!$idInbound) {
            throw new \RuntimeException('Gagal membuat nomor penerimaan');
        }

        return [$idInbound, $receptionNumber];
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
            "SELECT ih.*, s.name AS supplier_name FROM inbound_headers ih LEFT JOIN suppliers s ON s.id_supplier = ih.id_supplier WHERE ih.id_inbound = ? FOR UPDATE",
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

            [$batchNumber, $error] = $this->sanitizeText($detail['batch_number'] ?? null, self::BATCH_NUMBER_LENGTH, 'Baris ' . $row . ': Batch/Lot', true);
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
        $this->db->table('inbound_headers')
            ->where('id_inbound', $id)
            ->update([
                'status'     => 'COMPLETED',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function getAll(): array
    {
        $summarySql = $this->db->table('inbound_details')
            ->select("id_inbound, GROUP_CONCAT(DISTINCT expiry_date ORDER BY expiry_date SEPARATOR ', ') AS expiry_dates, MIN(expiry_date) AS nearest_expiry_date")
            ->groupBy('id_inbound')
            ->getCompiledSelect(false);

        $data = $this->db->query("
            SELECT ih.*, s.name AS supplier_name, s.supplier_code AS supplier_code,
                   ds.expiry_dates AS expiry_dates, ds.nearest_expiry_date AS nearest_expiry_date
            FROM inbound_headers ih
            LEFT JOIN suppliers s ON s.id_supplier = ih.id_supplier
            LEFT JOIN ($summarySql) ds ON ds.id_inbound = ih.id_inbound
            ORDER BY ih.created_at DESC
        ")->getResultArray();

        return [
            'status' => true,
            'data'   => $data,
        ];
    }

    public function getById(string $id): array
    {
        $header = $this->db
            ->table('inbound_headers ih')
            ->select('
                ih.*,
                s.name AS supplier_name,
                s.supplier_code AS supplier_code
            ')
            ->join('suppliers s', 's.id_supplier = ih.id_supplier', 'left')
            ->where('ih.id_inbound', $id)
            ->get()
            ->getRowArray();

        if (!$header) {
            return $this->fail('Inbound tidak ditemukan', 404);
        }

        $details = $this->db
            ->table('inbound_details id')
            ->select('
                id.*,
                p.name AS product_name,
                p.sku_code
            ')
            ->join('products p', 'p.id_product = id.id_product', 'left')
            ->where('id.id_inbound', $id)
            ->get()
            ->getResultArray();

        $header['details'] = $details;

        return [
            'status' => true,
            'data'   => $header,
        ];
    }

    public function getProductsBySupplier(string $supplierId): array
    {
        $supplier = $this->normalizeId($supplierId);
        if (!$supplier) {
            return ['status' => true, 'data' => []];
        }

        $products = $this->db
            ->table('products p')
            ->select('p.id_product, p.sku_code, p.name, p.unit, p.brand_name')
            ->where('p.id_supplier', $supplier)
            ->where('p.status', 'AKTIF')
            ->orderBy('p.name', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data'   => $products,
        ];
    }

    public function create(array $data, ?array $actor = null): array
    {
        [$actor, $error] = $this->validateActor($actor);
        if ($error !== null) {
            return $error;
        }

        [$header, $error] = $this->validateCreateHeader($data);
        if ($error !== null) {
            return $error;
        }

        [$details, $error] = $this->prepareDetails($data['details'] ?? [], $header['id_supplier']);
        if ($error !== null) {
            return $error;
        }

        return $this->runInTransaction(function () use ($header, $details, $actor) {
            [$idInbound, $receptionNumber] = $this->insertInboundHeader($header, $actor['id']);
            $this->insertInboundDetails($idInbound, $details);

            return [
                'status'  => true,
                'message' => 'Inbound berhasil dibuat',
                'data'    => ['id_inbound' => $idInbound, 'reception_number' => $receptionNumber],
            ];
        }, 'InboundService::create', 'Gagal membuat inbound. Silakan coba lagi.');
    }

    public function update(string $id, array $data): array
    {
        [$header, $error] = $this->getDraftHeader($id, 'Hanya inbound dengan status DRAFT yang bisa diubah');
        if ($error !== null) {
            return $error;
        }

        [$updateData, $details, $error] = $this->validateUpdatePayload($header, $data);
        if ($error !== null) {
            return $error;
        }

        return $this->runInTransaction(function () use ($id, $updateData, $details) {
            $this->db->table('inbound_headers')->where('id_inbound', $id)->update($updateData);

            if ($details !== null) {
                $error = $this->syncInboundDetails($id, $details);
                if ($error !== null) {
                    return $error;
                }
            }

            return ['status' => true, 'message' => 'Inbound berhasil diupdate'];
        }, 'InboundService::update', 'Gagal mengupdate inbound. Silakan coba lagi.');
    }

    public function delete(string $id): array
    {
        [$header, $error] = $this->getDraftHeader($id, 'Hanya inbound dengan status DRAFT yang bisa dihapus');
        if ($error !== null) {
            return $error;
        }

        return $this->runInTransaction(function () use ($id) {
            $this->db->table('inbound_details')->where('id_inbound', $id)->delete();
            $this->db->table('inbound_headers')->where('id_inbound', $id)->delete();

            return ['status' => true, 'message' => 'Inbound berhasil dihapus'];
        }, 'InboundService::delete', 'Gagal menghapus inbound. Silakan coba lagi.');
    }

    public function complete(string $id): array
    {
        return $this->runInTransaction(function () use ($id) {
            [$supplierId, $error] = $this->validateCompletionHeader($id);
            if ($error !== null) {
                return $error;
            }

            [$prepared, $error] = $this->prepareCompletionDetails($id, $supplierId);
            if ($error !== null) {
                return $error;
            }

            $this->applyStockMutations($prepared);
            $this->finalizeInboundHeader($id);

            return ['status' => true, 'message' => 'Inbound berhasil diselesaikan dan stok telah ditambahkan'];
        }, 'InboundService::complete', 'Gagal menyelesaikan inbound. Silakan coba lagi.');
    }
}

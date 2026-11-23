<?php

namespace App\Services;

use Config\Database;

class OutboundService
{
    private const LEGACY_MAX_ID = 35;
    private const MAX_QUANTITY = 2147483647;
    private const ALLOWED_ROLES = ['SUPERADMIN', 'ADMIN', 'GUDANG'];
    private const ADMIN_ROLES = ['SUPERADMIN', 'ADMIN'];

    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    private function ok(string $message, int $code = 200, ?array $data = null): array
    {
        $result = [
            'status'  => true,
            'message' => $message,
            'code'    => $code,
        ];

        if ($data !== null) {
            $result['data'] = $data;
        }

        return $result;
    }

    private function fail(string $message, int $code): array
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

            $this->db->transCommit();

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
            return [null, $this->fail('Unauthorized. Silakan login terlebih dahulu.', 401)];
        }

        $id = $this->parsePositiveInt($actor['id'] ?? null);
        if ($id === null) {
            return [null, $this->fail('Unauthorized. Silakan login terlebih dahulu.', 401)];
        }

        $role = strtoupper(trim((string)($actor['role'] ?? '')));
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            return [null, $this->fail('Forbidden. Anda tidak memiliki akses ke transaksi outbound.', 403)];
        }

        return [
            ['id' => $id, 'role' => $role],
            null,
        ];
    }

    private function canManage(array $actor, array $header): bool
    {
        if (in_array($actor['role'], self::ADMIN_ROLES, true)) {
            return true;
        }

        return (int)($header['created_by'] ?? 0) === $actor['id'];
    }

    private function parsePositiveInt($value, int $max = PHP_INT_MAX): ?int
    {
        if (is_int($value)) {
            return $value > 0 && $value <= $max ? $value : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => $max,
            ],
        ]);

        return $number === false ? null : $number;
    }

    private function isValidDate($value): bool
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function isLegacy(int $id): bool
    {
        return $id <= self::LEGACY_MAX_ID;
    }

    private function legacyMessage(): string
    {
        return 'Transaksi outbound lama perlu ditinjau terlebih dahulu';
    }

    private function getLockedHeader(int $id): ?array
    {
        $header = $this->db
            ->query('SELECT * FROM outbound_headers WHERE id_outbound = ? FOR UPDATE', [$id])
            ->getRowArray();

        return $header ?: null;
    }

    private function getLockedBatch(int $id): ?array
    {
        $batch = $this->db
            ->query('SELECT id_batch, id_product, batch_number, quantity FROM product_batches WHERE id_batch = ? FOR UPDATE', [$id])
            ->getRowArray();

        return $batch ?: null;
    }

    private function validateDraftHeader(int $idOutbound, array $actor, string $forbiddenMessage, string $statusMessage): ?array
    {
        $header = $this->getLockedHeader($idOutbound);
        if (!$header) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        if ($this->isLegacy($idOutbound)) {
            return $this->fail($this->legacyMessage(), 409);
        }

        if (!$this->canManage($actor, $header)) {
            return $this->fail($forbiddenMessage, 403);
        }

        if ($header['status'] !== 'DRAFT') {
            return $this->fail($statusMessage, 409);
        }

        return null;
    }

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

    private function sanitizeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim(strip_tags((string)$value));
    }

    private function generateDeliveryNumber(): string
    {
        do {
            $number = 'OUT-' . str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $this->db->table('outbound_headers')->where('delivery_number', $number)->countAllResults() > 0;
        } while ($exists);

        return $number;
    }

    public function getAll(): array
    {
        $data = $this->db
            ->table('outbound_headers oh')
            ->select('oh.*')
            ->orderBy('oh.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data'   => $data,
            'code'   => 200,
        ];
    }

    public function getById(string $id): array
    {
        $idOutbound = $this->parsePositiveInt($id);
        if ($idOutbound === null) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        $header = $this->db
            ->table('outbound_headers oh')
            ->select('oh.*')
            ->where('oh.id_outbound', $idOutbound)
            ->get()
            ->getRowArray();

        if (!$header) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        $details = $this->db
            ->table('outbound_details od')
            ->select('
                od.*,
                pb.id_product,
                p.name AS product_name,
                p.sku_code,
                pb.batch_number,
                pb.expiry_date
            ')
            ->join('product_batches pb', 'pb.id_batch = od.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('od.id_outbound', $idOutbound)
            ->get()
            ->getResultArray();

        $header['details'] = $details;

        return [
            'status' => true,
            'data'   => $header,
            'code'   => 200,
        ];
    }

    private function validateCreatePayload(array $data): array
    {
        if (!$this->isValidDate($data['delivery_date'] ?? null)) {
            return [null, $this->fail('Tanggal pengiriman wajib diisi dengan format YYYY-MM-DD', 400)];
        }

        if (!array_key_exists('details', $data) || !is_array($data['details'])) {
            return [null, $this->fail('Detail barang wajib diisi', 400)];
        }

        [$details, $error] = $this->normalizeDetails($data['details']);
        if ($error !== null) {
            return [null, $this->fail($error, 400)];
        }

        $deliveryNumber = $this->sanitizeText($data['delivery_number'] ?? null);
        if ($deliveryNumber === '') {
            $deliveryNumber = null;
        }

        return [[
            'delivery_date'   => $data['delivery_date'],
            'delivery_number' => $deliveryNumber,
            'notes'           => $this->sanitizeText($data['notes'] ?? null),
            'details'         => $details,
        ], null];
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

    public function create(array $data, ?array $actor = null): array
    {
        [$actor, $error] = $this->validateActor($actor);
        if ($error !== null) {
            return $error;
        }

        [$payload, $error] = $this->validateCreatePayload($data);
        if ($error !== null) {
            return $error;
        }

        return $this->runInTransaction(function () use ($actor, $payload) {
            $deliveryNumber = $payload['delivery_number'];
            if ($deliveryNumber === null) {
                $deliveryNumber = $this->generateDeliveryNumber();
            }

            $this->db->table('outbound_headers')->insert([
                'delivery_number' => $deliveryNumber,
                'delivery_date'   => $payload['delivery_date'],
                'notes'           => $payload['notes'],
                'status'          => 'DRAFT',
                'created_by'      => $actor['id'],
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $idOutbound = (int)$this->db->insertID();

            $this->insertOutboundDetails($idOutbound, $payload['details']);

            return $this->ok('Outbound berhasil dibuat', 201, ['id_outbound' => $idOutbound]);
        }, 'Gagal membuat outbound', 'Gagal membuat outbound');
    }

    private function normalizeUpdatePayload(array $data): array
    {
        $updateData = [];

        if (array_key_exists('delivery_number', $data)) {
            $deliveryNumber = $this->sanitizeText($data['delivery_number']);
            if ($deliveryNumber !== null && $deliveryNumber !== '') {
                $updateData['delivery_number'] = $deliveryNumber;
            }
        }

        if (array_key_exists('delivery_date', $data)) {
            if (!$this->isValidDate($data['delivery_date'])) {
                return [null, null, $this->fail('Tanggal pengiriman tidak valid', 400)];
            }
            $updateData['delivery_date'] = $data['delivery_date'];
        }

        if (array_key_exists('notes', $data)) {
            $updateData['notes'] = $this->sanitizeText($data['notes']);
        }

        $details = null;
        if (array_key_exists('details', $data)) {
            if (!is_array($data['details'])) {
                return [null, null, $this->fail('Detail barang tidak valid', 400)];
            }

            [$details, $error] = $this->normalizeDetails($data['details']);
            if ($error !== null) {
                return [null, null, $this->fail($error, 400)];
            }
        }

        return [$updateData, $details, null];
    }

    public function update(string $id, array $data, ?array $actor = null): array
    {
        [$actor, $error] = $this->validateActor($actor);
        if ($error !== null) {
            return $error;
        }

        $idOutbound = $this->parsePositiveInt($id);
        if ($idOutbound === null) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        return $this->runInTransaction(function () use ($idOutbound, $actor, $data) {
            $error = $this->validateDraftHeader(
                $idOutbound,
                $actor,
                'Forbidden. Anda hanya dapat mengubah transaksi outbound milik sendiri.',
                'Hanya outbound dengan status DRAFT yang bisa diubah'
            );
            if ($error !== null) {
                return $error;
            }

            [$updateData, $details, $error] = $this->normalizeUpdatePayload($data);
            if ($error !== null) {
                return $error;
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('outbound_headers')->where('id_outbound', $idOutbound)->update($updateData);

            if ($details !== null) {
                $this->db->table('outbound_details')->where('id_outbound', $idOutbound)->delete();
                $this->insertOutboundDetails($idOutbound, $details);
            }

            return $this->ok('Outbound berhasil diupdate');
        }, 'Gagal mengupdate outbound', 'Gagal mengupdate outbound');
    }

    public function delete(string $id, ?array $actor = null): array
    {
        [$actor, $error] = $this->validateActor($actor);
        if ($error !== null) {
            return $error;
        }

        $idOutbound = $this->parsePositiveInt($id);
        if ($idOutbound === null) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        return $this->runInTransaction(function () use ($idOutbound, $actor) {
            $error = $this->validateDraftHeader(
                $idOutbound,
                $actor,
                'Forbidden. Anda hanya dapat menghapus transaksi outbound milik sendiri.',
                'Hanya outbound dengan status DRAFT yang bisa dihapus'
            );
            if ($error !== null) {
                return $error;
            }

            $this->db->table('outbound_details')->where('id_outbound', $idOutbound)->delete();
            $this->db->table('outbound_headers')->where('id_outbound', $idOutbound)->delete();

            return $this->ok('Outbound berhasil dihapus');
        }, 'Gagal menghapus outbound', 'Gagal menghapus outbound');
    }

    private function validateCompleteHeader(int $idOutbound, array $actor): ?array
    {
        return $this->validateDraftHeader(
            $idOutbound,
            $actor,
            'Forbidden. Anda hanya dapat menyelesaikan transaksi outbound milik sendiri.',
            'Outbound sudah diselesaikan'
        );
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
            ->table('outbound_headers')
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

    public function complete(string $id, ?array $actor = null): array
    {
        [$actor, $error] = $this->validateActor($actor);
        if ($error !== null) {
            return $error;
        }

        $idOutbound = $this->parsePositiveInt($id);
        if ($idOutbound === null) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        return $this->runInTransaction(function () use ($idOutbound, $actor) {
            $error = $this->validateCompleteHeader($idOutbound, $actor);
            if ($error !== null) {
                return $error;
            }

            [$requirements, $error] = $this->normalizeCompleteDetails($idOutbound);
            if ($error !== null) {
                return $error;
            }

            $now = date('Y-m-d H:i:s');

            [$lockedBatches, $error] = $this->lockCompleteBatches($requirements);
            if ($error !== null) {
                return $error;
            }

            $error = $this->applyCompleteStockMutation($lockedBatches, $now);
            if ($error !== null) {
                return $error;
            }

            $error = $this->finalizeCompleteStatus($idOutbound, $now);
            if ($error !== null) {
                return $error;
            }

            return $this->ok('Outbound berhasil diselesaikan');
        }, 'Gagal menyelesaikan outbound', 'Gagal menyelesaikan outbound');
    }
}

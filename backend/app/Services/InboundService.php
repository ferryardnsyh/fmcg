<?php

namespace App\Services;

use App\Services\Concerns\PreparesInboundDetails;
use App\Services\Concerns\ServiceResults;
use App\Services\Concerns\ValidatesServiceActors;
use App\Support\ValueRules;
use App\Support\WmsPolicy;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

class InboundService
{
    use ServiceResults;
    use ValidatesServiceActors;
    use PreparesInboundDetails;

    protected $db;

    private const MAX_INT_COLUMN = 2147483647;
    private const DOCUMENT_NUMBER_LENGTH = 50;
    private const BATCH_NUMBER_LENGTH = 100;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function fail(string $message, int $code = 400): array
    {
        return $this->makeFailureResult($message, $code);
    }

    private function validateActor(?array $actor): array
    {
        return $this->validateServiceActor(
            $actor,
            WmsPolicy::MESSAGE_FORBIDDEN_INBOUND,
            true,
            'normalizeId'
        );
    }

    private function normalizeId($value): ?int
    {
        return ValueRules::positiveId($value);
    }

    private function parseQuantity($value, int $row): array
    {
        if (is_int($value)) {
            $quantity = $value;
        } else {
            $raw = ValueRules::stringifyScalar($value);
            if ($raw === null || $raw === '' || preg_match('/^\d+$/', $raw) !== 1) {
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

        $value = ValueRules::cleanScalarText($value);
        if ($value === null) {
            return [null, $this->fail($label . ' tidak valid')];
        }

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

    private function sanitizeDocumentText($value, int $maxLength, string $label, bool $required): array
    {
        [$value, $error] = $this->sanitizeText($value, $maxLength, $label, $required);
        if ($error !== null || $value === null) {
            return [$value, $error];
        }

        if (ValueRules::normalizeDocumentNumber($value, $maxLength) === null) {
            return [null, $this->fail($label . ' hanya boleh huruf, angka, spasi, titik, garis bawah, tanda hubung, atau garis miring')];
        }

        return [$value, null];
    }

    private function validateDate($value, string $label): array
    {
        $date = ValueRules::notPastIsoDate($value);
        if ($date === null) {
            $message = ($value === null || !is_string($value)) ? $label . ' wajib diisi' : $label . ' tidak valid atau sebelum hari ini';

            return [null, $this->fail($message)];
        }

        return [$date, null];
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
            ->where('status', WmsPolicy::STATUS_ACTIVE)
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

        $exists = $this->db->table('rack')
            ->where('id_rack', $rackId)
            ->where('status', WmsPolicy::STATUS_ACTIVE)
            ->countAllResults();

        if (!$exists) {
            return [null, $this->fail('Baris ' . $row . ': Rak tidak ditemukan atau tidak aktif')];
        }

        return [$rackId, null];
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

        [$poNumber, $error] = $this->sanitizeDocumentText($data['po_number'] ?? null, self::DOCUMENT_NUMBER_LENGTH, 'Nomor PO', true);
        if ($error !== null) {
            return [[], $error];
        }

        [$deliveryNoteNumber, $error] = $this->sanitizeDocumentText($data['delivery_note_number'] ?? null, self::DOCUMENT_NUMBER_LENGTH, 'Nomor Surat Jalan', true);
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
        $header = $this->db->table('inbound')->where('id_inbound', $id)->get()->getRowArray();
        if (!$header) {
            return [null, $this->fail('Inbound tidak ditemukan', 404)];
        }

        if ($header['status'] !== WmsPolicy::STATUS_DRAFT) {
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
            [$value, $error] = $this->sanitizeDocumentText($data['po_number'], self::DOCUMENT_NUMBER_LENGTH, 'Nomor PO', false);
            if ($error !== null) {
                return [[], null, $error];
            }
            $updateData['po_number'] = $value;
        }
        if (array_key_exists('delivery_note_number', $data)) {
            [$value, $error] = $this->sanitizeDocumentText($data['delivery_note_number'], self::DOCUMENT_NUMBER_LENGTH, 'Nomor Surat Jalan', false);
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
            $exists = $this->db->table('inbound')->where('reception_number', $number)->countAllResults() > 0;
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
                $this->db->table('inbound')->insert([
                    'reception_number'     => $receptionNumber,
                    'po_number'            => $header['po_number'],
                    'delivery_note_number' => $header['delivery_note_number'],
                    'id_supplier'          => $header['id_supplier'],
                    'reception_date'       => $header['reception_date'],
                    'notes'                => $header['notes'],
                    'status'               => WmsPolicy::STATUS_DRAFT,
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

    public function getAll(): array
    {
        $summarySql = $this->db->table('inbound_details')
            ->select("id_inbound, GROUP_CONCAT(DISTINCT expiry_date ORDER BY expiry_date SEPARATOR ', ') AS expiry_dates, MIN(expiry_date) AS nearest_expiry_date")
            ->groupBy('id_inbound')
            ->getCompiledSelect(false);

        $data = $this->db->query("
            SELECT ih.*, s.name AS supplier_name, s.supplier_code AS supplier_code,
                   ds.expiry_dates AS expiry_dates, ds.nearest_expiry_date AS nearest_expiry_date
            FROM inbound ih
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
            ->table('inbound ih')
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
            ->where('p.status', WmsPolicy::STATUS_ACTIVE)
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

        return $this->runServiceTransaction(function () use ($header, $details, $actor) {
            [$idInbound, $receptionNumber] = $this->insertInboundHeader($header, $actor['id']);
            $this->insertInboundDetails($idInbound, $details);

            return $this->makeSuccessResult('Inbound berhasil dibuat', null, [
                'id_inbound'       => $idInbound,
                'reception_number' => $receptionNumber,
            ]);
        }, 'InboundService::create', 'Gagal membuat inbound. Silakan coba lagi.', 500, true);
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

        return $this->runServiceTransaction(function () use ($id, $updateData, $details) {
            $this->db->table('inbound')->where('id_inbound', $id)->update($updateData);

            if ($details !== null) {
                $error = $this->syncInboundDetails($id, $details);
                if ($error !== null) {
                    return $error;
                }
            }

            return $this->makeSuccessResult('Inbound berhasil diupdate');
        }, 'InboundService::update', 'Gagal mengupdate inbound. Silakan coba lagi.', 500, true);
    }

    public function delete(string $id): array
    {
        [$header, $error] = $this->getDraftHeader($id, 'Hanya inbound dengan status DRAFT yang bisa dihapus');
        if ($error !== null) {
            return $error;
        }

        return $this->runServiceTransaction(function () use ($id) {
            $this->db->table('inbound_details')->where('id_inbound', $id)->delete();
            $this->db->table('inbound')->where('id_inbound', $id)->delete();

            return $this->makeSuccessResult('Inbound berhasil dihapus');
        }, 'InboundService::delete', 'Gagal menghapus inbound. Silakan coba lagi.', 500, true);
    }

    public function complete(string $id): array
    {
        return $this->runServiceTransaction(function () use ($id) {
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

            return $this->makeSuccessResult('Inbound berhasil diselesaikan dan stok telah ditambahkan');
        }, 'InboundService::complete', 'Gagal menyelesaikan inbound. Silakan coba lagi.', 500, true);
    }
}

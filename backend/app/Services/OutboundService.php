<?php

namespace App\Services;

use App\Services\Concerns\NormalizesOutboundDetails;
use App\Services\Concerns\ServiceResults;
use App\Services\Concerns\ValidatesServiceActors;
use App\Support\ValueRules;
use App\Support\WmsPolicy;
use Config\Database;

class OutboundService
{
    use ServiceResults;
    use ValidatesServiceActors;
    use NormalizesOutboundDetails;

    private const MAX_QUANTITY = 2147483647;

    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    private function ok(string $message, int $code = 200, ?array $data = null): array
    {
        return $this->makeSuccessResult($message, $code, $data);
    }

    private function fail(string $message, int $code): array
    {
        return $this->makeFailureResult($message, $code);
    }

    private function validateActor(?array $actor): array
    {
        return $this->validateServiceActor(
            $actor,
            WmsPolicy::MESSAGE_FORBIDDEN_OUTBOUND,
            false,
            'parsePositiveInt'
        );
    }

    private function canManage(array $actor, array $header): bool
    {
        if (in_array($actor['role'], WmsPolicy::ADMIN_ROLES, true)) {
            return true;
        }

        return (int)($header['created_by'] ?? 0) === $actor['id'];
    }

    private function parsePositiveInt($value, int $max = PHP_INT_MAX): ?int
    {
        return ValueRules::positiveInt($value, $max);
    }

    private function isValidDate($value): bool
    {
        return ValueRules::notPastIsoDate($value) !== null;
    }

    private function getLockedHeader(int $id): ?array
    {
        $header = $this->db
            ->query('SELECT * FROM outbound WHERE id_outbound = ? FOR UPDATE', [$id])
            ->getRowArray();

        return $header ?: null;
    }

    private function validateDraftHeader(int $idOutbound, array $actor, string $forbiddenMessage, string $statusMessage): ?array
    {
        $header = $this->getLockedHeader($idOutbound);
        if (!$header) {
            return $this->fail('Outbound tidak ditemukan', 404);
        }

        if (!$this->canManage($actor, $header)) {
            return $this->fail($forbiddenMessage, 403);
        }

        if ($header['status'] !== WmsPolicy::STATUS_DRAFT) {
            return $this->fail($statusMessage, 409);
        }

        return null;
    }

    private function sanitizeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return ValueRules::cleanText($value);
    }

    private function generateDeliveryNumber(): string
    {
        do {
            $number = 'OUT-' . str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $this->db->table('outbound')->where('delivery_number', $number)->countAllResults() > 0;
        } while ($exists);

        return $number;
    }

    public function getAll(): array
    {
        $data = $this->db
            ->table('outbound oh')
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
            ->table('outbound oh')
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
            return [null, $this->fail('Tanggal pengiriman wajib diisi dengan format YYYY-MM-DD dan tidak boleh sebelum hari ini', 400)];
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
        if ($deliveryNumber !== null && ValueRules::normalizeDocumentNumber($deliveryNumber, 50) === null) {
            return [null, $this->fail('Nomor pengiriman hanya boleh huruf, angka, spasi, titik, garis bawah, tanda hubung, atau garis miring', 400)];
        }

        $notes = $this->sanitizeText($data['notes'] ?? null);
        if ($notes !== null && mb_strlen($notes) > 65535) {
            return [null, $this->fail('Catatan maksimal 65535 karakter', 400)];
        }

        return [[
            'delivery_date'   => $data['delivery_date'],
            'delivery_number' => $deliveryNumber,
            'notes'           => $notes,
            'details'         => $details,
        ], null];
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

        return $this->runServiceTransaction(function () use ($actor, $payload) {
            $deliveryNumber = $payload['delivery_number'];
            if ($deliveryNumber === null) {
                $deliveryNumber = $this->generateDeliveryNumber();
            }

            $this->db->table('outbound')->insert([
                'delivery_number' => $deliveryNumber,
                'delivery_date'   => $payload['delivery_date'],
                'notes'           => $payload['notes'],
                'status'          => WmsPolicy::STATUS_DRAFT,
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
                if (ValueRules::normalizeDocumentNumber($deliveryNumber, 50) === null) {
                    return [null, null, $this->fail('Nomor pengiriman hanya boleh huruf, angka, spasi, titik, garis bawah, tanda hubung, atau garis miring', 400)];
                }
                $updateData['delivery_number'] = $deliveryNumber;
            }
        }

        if (array_key_exists('delivery_date', $data)) {
            if (!$this->isValidDate($data['delivery_date'])) {
                return [null, null, $this->fail('Tanggal pengiriman tidak valid atau sebelum hari ini', 400)];
            }
            $updateData['delivery_date'] = $data['delivery_date'];
        }

        if (array_key_exists('notes', $data)) {
            $notes = $this->sanitizeText($data['notes']);
            if ($notes !== null && mb_strlen($notes) > 65535) {
                return [null, null, $this->fail('Catatan maksimal 65535 karakter', 400)];
            }
            $updateData['notes'] = $notes;
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

        return $this->runServiceTransaction(function () use ($idOutbound, $actor, $data) {
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
            $this->db->table('outbound')->where('id_outbound', $idOutbound)->update($updateData);

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

        return $this->runServiceTransaction(function () use ($idOutbound, $actor) {
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
            $this->db->table('outbound')->where('id_outbound', $idOutbound)->delete();

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

        return $this->runServiceTransaction(function () use ($idOutbound, $actor) {
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

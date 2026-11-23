<?php

namespace App\Services\Master;

use App\Validation\MasterInput;
use Config\Database;
use Throwable;

class RackService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getRacks(array $params): array
    {
        $searchParam = $params['search'] ?? '';
        $search = is_string($searchParam) ? trim($searchParam) : '';
        $statusParam = $params['status'] ?? '';
        $status = is_string($statusParam) ? strtoupper(trim($statusParam)) : '';
        $page = MasterInput::toPositiveInt($params['page'] ?? 1, 1);
        $limit = min(100, MasterInput::toPositiveInt($params['limit'] ?? 100, 100));
        $offset = ($page - 1) * $limit;
        $sortParam = $params['sort'] ?? 'rack_code';
        $sortField = is_string($sortParam) ? $sortParam : 'rack_code';
        $sort = in_array($sortField, ['id_rack', 'rack_code', 'name', 'status'], true) ? $sortField : 'rack_code';
        $orderParam = $params['order'] ?? 'ASC';
        $order = is_string($orderParam) && strtoupper($orderParam) === 'DESC' ? 'DESC' : 'ASC';

        $builder = $this->db->table('rack_locations r')
            ->select('r.id_rack, r.rack_code, r.name, r.status');

        if ($search !== '') {
            $escaped = $this->db->escapeLikeString($search);
            $builder->where("(r.name LIKE '%{$escaped}%' OR r.rack_code LIKE '%{$escaped}%')", null, false);
        }
        if ($status === 'AKTIF') {
            $builder->where('r.status', 'AKTIF');
        } elseif ($status === 'NONAKTIF') {
            $builder->where('r.status', 'NONAKTIF');
        }

        $total = (clone $builder)->countAllResults();

        $data = $builder
            ->orderBy("r.{$sort}", $order)
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        $statsTotal = (int) $this->db->table('rack_locations')->countAllResults();
        $statsAktif = (int) $this->db->table('rack_locations')->where('status', 'AKTIF')->countAllResults();

        return [
            'status' => true,
            'data' => [
                'items' => $data,
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => max(1, (int)ceil($total / $limit)),
                'stats' => [
                    'total' => $statsTotal,
                    'aktif' => $statsAktif,
                    'nonAktif' => $statsTotal - $statsAktif,
                ],
            ],
        ];
    }

    public function getRack($id): array
    {
        $data = $this->db->table('rack_locations')
            ->select('id_rack, rack_code, name, status')
            ->where('id_rack', $id)
            ->get()
            ->getRowArray();

        if (!$data) {
            return $this->fail('Rak tidak ditemukan', 404);
        }

        return ['status' => true, 'data' => $data];
    }

    public function createRack(mixed $data): array
    {
        $data = is_array($data) ? $data : [];

        $code = MasterInput::cleanRequired($data['rack_code'] ?? null);
        $name = MasterInput::cleanOptional($data['name'] ?? null);

        if ($code === null) {
            return $this->fail('Kode rak wajib diisi', 400);
        }

        if ($name === false) {
            return $this->fail('Nama rak tidak valid', 400);
        }

        if (mb_strlen($code) > 50) {
            return $this->fail('Kode rak maksimal 50 karakter', 400);
        }

        if ($name !== null && mb_strlen($name) > 100) {
            return $this->fail('Nama rak maksimal 100 karakter', 400);
        }

        [$status, $statusError] = MasterInput::resolveRackStatus($data, 'AKTIF');
        if ($statusError !== null) {
            return $this->fail($statusError, 400);
        }

        $codeExists = $this->db->table('rack_locations')
            ->where('rack_code', $code)
            ->countAllResults();
        if ($codeExists > 0) {
            return $this->fail('Kode rak sudah digunakan', 409);
        }

        $this->db->table('rack_locations')->insert([
            'rack_code' => $code,
            'name' => $name,
            'status' => $status,
        ]);
        $id = (int) $this->db->insertID();
        if ($id <= 0) {
            throw new \RuntimeException('Gagal mendapatkan ID rak');
        }

        return [
            'status' => true,
            'message' => 'Rak berhasil ditambahkan',
            'data' => ['id_rack' => $id, 'rack_code' => $code],
            'code' => 201,
        ];
    }

    public function updateRack($id, mixed $data): array
    {
        $existing = $this->db->table('rack_locations')->where('id_rack', $id)->get()->getRowArray();
        if (!$existing) {
            return $this->fail('Rak tidak ditemukan', 404);
        }

        if (!is_array($data)) {
            return $this->fail('Data request tidak valid. Pastikan mengirimkan JSON.', 400);
        }

        $code = MasterInput::cleanRequired($data['rack_code'] ?? $existing['rack_code']);
        $name = array_key_exists('name', $data)
            ? MasterInput::cleanOptional($data['name'])
            : $existing['name'];

        if ($code === null) {
            return $this->fail('Kode rak wajib diisi', 400);
        }

        if ($name === false) {
            return $this->fail('Nama rak tidak valid', 400);
        }

        if (mb_strlen($code) > 50) {
            return $this->fail('Kode rak maksimal 50 karakter', 400);
        }

        if ($name !== null && mb_strlen($name) > 100) {
            return $this->fail('Nama rak maksimal 100 karakter', 400);
        }

        [$status, $statusError] = MasterInput::resolveRackStatus($data, (string) $existing['status']);
        if ($statusError !== null) {
            return $this->fail($statusError, 400);
        }

        $duplicate = $this->db->table('rack_locations')
            ->where('rack_code', $code)
            ->where('id_rack !=', $id)
            ->countAllResults();
        if ($duplicate > 0) {
            return $this->fail('Kode rak sudah digunakan', 409);
        }

        $this->db->table('rack_locations')->where('id_rack', $id)->update([
            'rack_code' => $code,
            'name' => $name,
            'status' => $status,
        ]);

        return [
            'status' => true,
            'message' => 'Rak berhasil diupdate',
        ];
    }

    public function deleteRack($id): array
    {
        $existing = $this->db->table('rack_locations')->where('id_rack', $id)->get()->getRowArray();
        if (!$existing) {
            return $this->fail('Rak tidak ditemukan', 404);
        }

        $batchCount = (int) $this->db->table('product_batches')
            ->where('id_rack', $id)
            ->countAllResults();
        $inboundCount = (int) $this->db->table('inbound_details')
            ->where('id_rack', $id)
            ->countAllResults();

        $reasons = [];
        if ($batchCount > 0) {
            $reasons[] = "{$batchCount} batch stok produk";
        }
        if ($inboundCount > 0) {
            $reasons[] = "{$inboundCount} riwayat barang masuk";
        }

        if ($reasons !== []) {
            return $this->fail(
                'Rak tidak bisa dihapus karena sedang digunakan oleh ' . implode(' dan ', $reasons) . '. Nonaktifkan rak saja.',
                409
            );
        }

        $this->db->table('rack_locations')->where('id_rack', $id)->delete();

        return [
            'status' => true,
            'message' => 'Rak berhasil dihapus',
        ];
    }

    public function isDuplicateCodeConflict(Throwable $e): bool
    {
        return MasterInput::isDuplicateConflict($e, 'rack_code');
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

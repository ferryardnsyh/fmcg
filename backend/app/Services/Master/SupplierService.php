<?php

namespace App\Services\Master;

use App\Models\SupplierModel;
use App\Validation\MasterInput;
use Config\Database;
use Throwable;

class SupplierService
{
    protected SupplierModel $supplier;

    public function __construct()
    {
        $this->supplier = new SupplierModel();
    }

    public function getSuppliers(): array
    {
        $db = Database::connect();
        $data = $db->table('suppliers s')
            ->select('s.*')
            ->orderBy('s.id_supplier', 'ASC')
            ->get()
            ->getResultArray();

        return ['status' => true, 'data' => $data];
    }

    public function getSupplier($id): array
    {
        $data = $this->supplier->find($id);

        if (!$data) {
            return $this->fail('Supplier tidak ditemukan', 404);
        }

        return ['status' => true, 'data' => $data];
    }

    public function createSupplier(mixed $data): array
    {
        $data = is_array($data) ? $data : [];

        $code = MasterInput::cleanRequired($data['supplier_code'] ?? null);
        $name = MasterInput::cleanRequired($data['name'] ?? null);

        if ($code === null || $name === null) {
            return $this->fail('Field supplier_code dan name wajib diisi', 400);
        }

        if (mb_strlen($code) > 50) {
            return $this->fail('Kode supplier maksimal 50 karakter', 400);
        }

        if (mb_strlen($name) > 200) {
            return $this->fail('Nama supplier maksimal 200 karakter', 400);
        }

        [$status, $statusError] = MasterInput::resolveSupplierStatus($data, 'AKTIF');
        if ($statusError !== null) {
            return $this->fail($statusError, 400);
        }

        $phone = MasterInput::cleanOptional($data['phone'] ?? null);
        if ($phone === false) {
            return $this->fail('Nomor telepon tidak valid', 400);
        }
        if ($phone !== null && mb_strlen($phone) > 50) {
            return $this->fail('Nomor telepon maksimal 50 karakter', 400);
        }
        if ($phone !== null && !preg_match('/^[0-9+\-()\/.\s]+$/u', $phone)) {
            return $this->fail('Format nomor telepon tidak valid', 400);
        }

        $address = MasterInput::cleanOptional($data['address'] ?? null);
        if ($address === false) {
            return $this->fail('Alamat tidak valid', 400);
        }

        $exists = $this->supplier->where('supplier_code', $code)->first();
        if ($exists) {
            return $this->fail('Kode supplier sudah digunakan', 409);
        }

        $this->supplier->insert([
            'supplier_code' => $code,
            'name'          => $name,
            'phone'         => $phone,
            'address'       => $address,
            'status'        => $status,
        ]);

        if ($this->supplier->errors()) {
            return [
                'status'  => false,
                'message' => 'Gagal menambahkan supplier',
                'errors'  => $this->supplier->errors(),
                'code'    => 400,
            ];
        }

        $insertId = (int) $this->supplier->insertID();
        $created = $this->supplier->find($insertId);
        $storedId = is_array($created) && isset($created['id_supplier'])
            ? (int) $created['id_supplier']
            : $insertId;

        return [
            'status'  => true,
            'message' => 'Supplier berhasil ditambahkan',
            'data'    => ['id_supplier' => $storedId],
            'code'    => 201,
        ];
    }

    public function updateSupplier($id, mixed $data): array
    {
        $existing = $this->supplier->find($id);
        if (!$existing) {
            return $this->fail('Supplier tidak ditemukan', 404);
        }

        if (!is_array($data)) {
            return $this->fail('Data request tidak valid. Pastikan mengirimkan JSON.', 400);
        }

        $code = MasterInput::cleanRequired($data['supplier_code'] ?? null);
        $name = MasterInput::cleanRequired($data['name'] ?? null);

        if ($code === null || $name === null) {
            return $this->fail('Field supplier_code dan name wajib diisi', 400);
        }

        if (mb_strlen($code) > 50) {
            return $this->fail('Kode supplier maksimal 50 karakter', 400);
        }

        if (mb_strlen($name) > 200) {
            return $this->fail('Nama supplier maksimal 200 karakter', 400);
        }

        [$status, $statusError] = MasterInput::resolveSupplierStatus($data, $existing['status'] ?? 'AKTIF');
        if ($statusError !== null) {
            return $this->fail($statusError, 400);
        }

        $phone = MasterInput::cleanOptional($data['phone'] ?? null);
        if ($phone === false) {
            return $this->fail('Nomor telepon tidak valid', 400);
        }
        if ($phone !== null && mb_strlen($phone) > 50) {
            return $this->fail('Nomor telepon maksimal 50 karakter', 400);
        }
        if ($phone !== null && !preg_match('/^[0-9+\-()\/.\s]+$/u', $phone)) {
            return $this->fail('Format nomor telepon tidak valid', 400);
        }

        $address = MasterInput::cleanOptional($data['address'] ?? null);
        if ($address === false) {
            return $this->fail('Alamat tidak valid', 400);
        }

        $codeExists = $this->supplier
            ->where('supplier_code', $code)
            ->where('id_supplier !=', $id)
            ->first();

        if ($codeExists) {
            return $this->fail('Kode supplier sudah digunakan', 409);
        }

        $this->supplier->update($id, [
            'supplier_code' => $code,
            'name'          => $name,
            'phone'         => $phone,
            'address'       => $address,
            'status'        => $status,
        ]);

        if ($this->supplier->errors()) {
            return [
                'status'  => false,
                'message' => 'Gagal mengupdate supplier',
                'errors'  => $this->supplier->errors(),
                'code'    => 400,
            ];
        }

        return [
            'status'  => true,
            'message' => 'Supplier berhasil diupdate',
        ];
    }

    public function deleteSupplier($id): array
    {
        $existing = $this->supplier->find($id);
        if (!$existing) {
            return $this->fail('Supplier tidak ditemukan', 404);
        }

        $db = Database::connect();
        $productCount = $db->table('products')->where('id_supplier', $id)->countAllResults();
        $inboundCount = $db->table('inbound_headers')->where('id_supplier', $id)->countAllResults();

        if ($productCount > 0 || $inboundCount > 0) {
            $reasons = [];
            if ($productCount > 0) {
                $reasons[] = "{$productCount} produk";
            }
            if ($inboundCount > 0) {
                $reasons[] = "{$inboundCount} transaksi inbound";
            }

            return $this->fail(
                'Supplier tidak bisa dihapus karena masih digunakan oleh ' . implode(' dan ', $reasons) . '. Nonaktifkan saja.',
                409
            );
        }

        $this->supplier->delete($id);

        return [
            'status'  => true,
            'message' => 'Supplier berhasil dihapus',
        ];
    }

    public function isDuplicateCodeConflict(Throwable $e): bool
    {
        return MasterInput::isDuplicateConflict($e, 'supplier_code');
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

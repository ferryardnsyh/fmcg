<?php

namespace App\Services\Master;

use App\Support\ValueRules;
use App\Validation\MasterInput;
use Config\Database;
use Throwable;

class CategoryService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getCategories(array $params): array
    {
        $search = trim($params['search'] ?? '');
        $status = $params['status'] ?? '';
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(100, (int)($params['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $sortField = $params['sort'] ?? 'id_category';
        $sort = in_array($sortField, ['id_category', 'category_code', 'name', 'created_at', 'total_products']) ? $sortField : 'id_category';
        $order = strtoupper($params['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $builder = $this->db->table('categories c')
            ->select("
                c.id_category,
                c.category_code,
                c.name,
                c.status,
                c.created_at,
                c.updated_at,
                COUNT(p.id_product) AS total_products
            ")
            ->join('products p', 'p.id_category = c.id_category', 'left');

        $where = [];
        if ($search !== '') {
            $escaped = $this->db->escapeLikeString($search);
            $where[] = "(c.name LIKE '%{$escaped}%' OR c.category_code LIKE '%{$escaped}%')";
        }
        if ($status === 'active') {
            $where[] = "c.status = 'AKTIF'";
        } elseif ($status === 'inactive') {
            $where[] = "c.status = 'NONAKTIF'";
        }

        if (!empty($where)) {
            $builder->where(implode(' AND ', $where), null, false);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->groupBy('c.id_category')->countAllResults();

        $builder->groupBy('c.id_category');
        if ($sort === 'total_products') {
            $builder->orderBy('total_products', $order);
        } else {
            $builder->orderBy("c.{$sort}", $order);
        }

        $data = $builder
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => [
                'items' => $data,
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => max(1, (int)ceil($total / $limit)),
            ],
        ];
    }

    public function getAllCategories(): array
    {
        $data = $this->db->table('categories')
            ->select('id_category, category_code, name, status')
            ->where('status', 'AKTIF')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        return ['status' => true, 'data' => $data];
    }

    public function getCategory($id): array
    {
        $data = $this->db->table('categories c')
            ->select("
                c.id_category, c.category_code, c.name, c.status,
                c.created_at, c.updated_at,
                COUNT(p.id_product) AS total_products
            ")
            ->join('products p', 'p.id_category = c.id_category', 'left')
            ->where('c.id_category', $id)
            ->groupBy('c.id_category')
            ->get()
            ->getRowArray();

        if (!$data) {
            return $this->fail('Kategori tidak ditemukan', 404);
        }

        return ['status' => true, 'data' => $data];
    }

    public function nextCode(): array
    {
        return [
            'status' => true,
            'data' => ['category_code' => $this->generateCode()],
        ];
    }

    public function createCategory(mixed $data): array
    {
        $data = $data ?? [];

        $name = MasterInput::sanitize(trim($data['name'] ?? '')) ?? '';
        $rawCode = MasterInput::cleanOptional($data['category_code'] ?? null);
        if ($rawCode === false) {
            return $this->fail('Kode kategori tidak valid', 400);
        }

        $code = $rawCode === null ? '' : ValueRules::normalizeCode($rawCode, 10);
        if ($code === null) {
            return $this->fail('Kode kategori hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung (maksimal 10 karakter)', 400);
        }

        if ($name === '') {
            return $this->fail('Nama kategori wajib diisi', 400);
        }

        if (mb_strlen($name) > 200) {
            return $this->fail('Nama kategori maksimal 200 karakter', 400);
        }

        $status = array_key_exists('status', $data) ? $data['status'] : 'AKTIF';
        if (!is_string($status) || !in_array($status, ['AKTIF', 'NONAKTIF'], true)) {
            return $this->fail('Status kategori tidak valid', 400);
        }

        $exists = $this->db->table('categories')
            ->where('name', $name)
            ->countAllResults();

        if ($exists > 0) {
            return $this->fail('Nama kategori sudah digunakan', 409);
        }

        if ($code !== '') {
            $codeExists = $this->db->table('categories')
                ->where('category_code', $code)
                ->countAllResults();
            if ($codeExists > 0) {
                return $this->fail('Kode kategori sudah digunakan', 409);
            }
        } else {
            $code = $this->generateCode();
        }

        $this->db->table('categories')->insert([
            'category_code' => $code,
            'name' => $name,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = $this->db->insertID();

        return [
            'status' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => ['id_category' => $id, 'category_code' => $code],
            'code' => 201,
        ];
    }

    public function updateCategory($id, mixed $data): array
    {
        $existing = $this->db->table('categories')->where('id_category', $id)->get()->getRowArray();
        if (!$existing) {
            return $this->fail('Kategori tidak ditemukan', 404);
        }

        $data = $data ?? [];

        if (array_key_exists('category_code', $data)) {
            $requestedCode = $data['category_code'];
            if (!is_string($requestedCode) || trim($requestedCode) !== (string) $existing['category_code']) {
                return $this->fail('Kode kategori tidak dapat diubah', 400);
            }
        }

        $name = trim($data['name'] ?? '');
        $status = array_key_exists('status', $data) ? $data['status'] : $existing['status'];

        $name = MasterInput::sanitize($name) ?? '';

        if ($name === '') {
            return $this->fail('Nama kategori wajib diisi', 400);
        }

        if (mb_strlen($name) > 200) {
            return $this->fail('Nama kategori maksimal 200 karakter', 400);
        }

        if (!is_string($status) || !in_array($status, ['AKTIF', 'NONAKTIF'], true)) {
            return $this->fail('Status kategori tidak valid', 400);
        }

        $duplicate = $this->db->table('categories')
            ->where('name', $name)
            ->where('id_category !=', $id)
            ->countAllResults();

        if ($duplicate > 0) {
            return $this->fail('Nama kategori sudah digunakan', 409);
        }

        $this->db->transBegin();

        try {
            $this->db->table('categories')->where('id_category', $id)->update([
                'name' => $name,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('products')
                ->where('id_category', $id)
                ->update(['category' => $name]);

            if (!$this->db->transCommit()) {
                throw new \RuntimeException('Gagal menyimpan perubahan kategori');
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'CategoryService::updateCategory: ' . $e->getMessage());

            return $this->fail('Gagal mengupdate kategori', 500);
        }

        return [
            'status' => true,
            'message' => 'Kategori berhasil diupdate',
        ];
    }

    public function deleteCategory($id): array
    {
        $existing = $this->db->table('categories')->where('id_category', $id)->get()->getRowArray();
        if (!$existing) {
            return $this->fail('Kategori tidak ditemukan', 404);
        }

        $productCount = $this->db->table('products')
            ->where('id_category', $id)
            ->countAllResults();

        if ($productCount > 0) {
            return $this->fail(
                "Kategori tidak bisa dihapus karena sedang digunakan oleh {$productCount} produk. Nonaktifkan kategori saja.",
                409
            );
        }

        $this->db->table('categories')->where('id_category', $id)->delete();

        return [
            'status' => true,
            'message' => 'Kategori berhasil dihapus',
        ];
    }

    public function isDuplicateCodeConflict(Throwable $e): bool
    {
        return MasterInput::isDuplicateConflict($e, 'idx_categories_code_unique');
    }

    private function generateCode(): string
    {
        $row = $this->db->query("SELECT MAX(CAST(SUBSTRING(category_code, 4) AS UNSIGNED)) AS max_num FROM categories")->getRowArray();
        $next = (int)($row['max_num'] ?? 0) + 1;

        return 'CAT' . str_pad($next, 3, '0', STR_PAD_LEFT);
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

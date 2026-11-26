<?php

namespace App\Services;

use App\Support\ValueRules;
use App\Support\WmsPolicy;
use App\Validation\MasterInput;
use Config\Database;

class ProductService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getStatistic(): array
    {
        $result = $this->db
            ->table('products')
            ->select("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'AKTIF' THEN 1 ELSE 0 END) AS aktif,
                SUM(CASE WHEN status = 'NONAKTIF' THEN 1 ELSE 0 END) AS non_aktif
            ")
            ->get()
            ->getRowArray();

        $minimumStock = $this->db
            ->query("
                SELECT COUNT(*) AS total FROM (
                    SELECT p.id_product
                    FROM products p
                    LEFT JOIN product_batches pb ON pb.id_product = p.id_product
                    GROUP BY p.id_product, p.minimum_stock
                    HAVING COALESCE(SUM(pb.quantity), 0) <= p.minimum_stock
                        AND COALESCE(SUM(pb.quantity), 0) > 0
                ) AS low_stock
            ")
            ->getRowArray();
        $minimumStock = (int)($minimumStock['total'] ?? 0);

        return [
            'status' => true,
            'data' => [
                'total_produk' => (int)($result['total'] ?? 0),
                'produk_aktif' => (int)($result['aktif'] ?? 0),
                'minimum_stock' => $minimumStock,
                'non_aktif' => (int)($result['non_aktif'] ?? 0)
            ]
        ];
    }

    public function getProducts(?string $supplierId = null, ?string $search = null): array
    {
        $builder = $this->db
            ->table('products p')
            ->select('
                p.id_product,
                p.sku_code,
                p.name,
                p.id_supplier,
                p.id_category,
                p.category,
                p.brand_name,
                p.brand_name AS brand,
                c.name AS category_name,
                s.name AS supplier,
                p.unit,
                p.minimum_stock,
                p.status
            ')
            ->join('categories c', 'c.id_category = p.id_category', 'left')
            ->join('suppliers s', 's.id_supplier = p.id_supplier', 'left');

        if ($supplierId) {
            $builder->where('p.id_supplier', $supplierId);
        }

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $builder->groupStart();
            $builder->like('p.name', $searchTerm);
            $builder->orLike('p.sku_code', $searchTerm);
            $builder->groupEnd();
        }

        $products = $builder
            ->orderBy('p.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $products
        ];
    }

    public function getProductById(string $id): array
    {
        $product = $this->db
            ->table('products p')
            ->select('
                p.*,
                c.name AS category_name,
                s.name AS supplier
            ')
            ->join('categories c', 'c.id_category = p.id_category', 'left')
            ->join('suppliers s', 's.id_supplier = p.id_supplier', 'left')
            ->where('p.id_product', $id)
            ->get()
            ->getRowArray();

        if (!$product) {
            return [
                'status' => false,
                'message' => 'Produk tidak ditemukan'
            ];
        }

        return [
            'status' => true,
            'data' => $product
        ];
    }

    private function categoryName(mixed $id): ?string
    {
        if (empty($id)) {
            return null;
        }

        $category = $this->db->table('categories')
            ->select('name')
            ->where('id_category', $id)
            ->get()
            ->getRowArray();

        return $category['name'] ?? null;
    }

    public function createProduct(array $data): array
    {
        $name = ValueRules::cleanText($data['name'] ?? '');
        $sku = ValueRules::cleanText($data['sku_code'] ?? '');
        $brandName = MasterInput::cleanOptional($data['brand_name'] ?? null);
        $unit = ValueRules::cleanText($data['unit'] ?? '');
        $status = strtoupper(trim((string) ($data['status'] ?? WmsPolicy::STATUS_ACTIVE)));
        $categoryId = ValueRules::positiveId($data['id_category'] ?? null);
        $supplierId = ValueRules::positiveId($data['id_supplier'] ?? null);

        if ($name === '' || mb_strlen($name) > 200) {
            return ['status' => false, 'message' => 'Nama produk wajib diisi dan maksimal 200 karakter'];
        }

        if ($sku === '' || ValueRules::normalizeCode($sku, 50) === null) {
            return ['status' => false, 'message' => 'SKU code wajib diisi dan hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung (maksimal 50 karakter)'];
        }

        if ($brandName === false || ($brandName !== null && mb_strlen($brandName) > 200)) {
            return ['status' => false, 'message' => 'Brand maksimal 200 karakter'];
        }

        if ($unit === '' || mb_strlen($unit) > 50) {
            return ['status' => false, 'message' => 'Satuan wajib diisi dan maksimal 50 karakter'];
        }

        if (!in_array($status, [WmsPolicy::STATUS_ACTIVE, 'NONAKTIF'], true)) {
            return ['status' => false, 'message' => 'Status produk tidak valid'];
        }

        if ($categoryId === null || $supplierId === null) {
            return ['status' => false, 'message' => 'Kategori dan supplier wajib dipilih'];
        }

        $categoryExists = $this->db->table('categories')
            ->where('id_category', $categoryId)
            ->where('status', WmsPolicy::STATUS_ACTIVE)
            ->countAllResults();
        $supplierExists = $this->db->table('suppliers')
            ->where('id_supplier', $supplierId)
            ->where('status', WmsPolicy::STATUS_ACTIVE)
            ->countAllResults();

        if ($categoryExists === 0 || $supplierExists === 0) {
            return ['status' => false, 'message' => 'Kategori atau supplier tidak ditemukan / tidak aktif'];
        }

        $existingSku = $this->db->table('products')->where('sku_code', $sku)->countAllResults();
        if ($existingSku > 0) {
            return [
                'status' => false,
                'message' => 'SKU code sudah digunakan'
            ];
        }

        $minimumStock = MasterInput::parseInteger($data['minimum_stock'] ?? 10, 0, 2147483647);
        if ($minimumStock === null) {
            return [
                'status' => false,
                'message' => 'Minimum stock harus bilangan bulat antara 0 dan 2147483647'
            ];
        }

        $productData = [
            'sku_code'       => $sku,
            'name'           => $name,
            'category'       => $this->categoryName($categoryId),
            'id_supplier'    => $supplierId,
            'id_category'    => $categoryId,
            'brand_name'     => $brandName,
            'unit'           => $unit,
            'minimum_stock'  => $minimumStock,
            'status'         => $status,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->table('products')->insert($productData);

        if ($this->db->affectedRows() > 0) {
            return [
                'status' => true,
                'message' => 'Produk berhasil ditambahkan',
                'data' => ['id_product' => $this->db->insertID()]
            ];
        }

        return [
            'status' => false,
            'message' => 'Gagal menambahkan produk'
        ];
    }

    public function updateProduct(string $id, array $data): array
    {
        $name = null;
        $sku = null;
        $brandName = null;
        $unit = null;
        $status = null;
        $categoryId = null;
        $supplierId = null;

        if (array_key_exists('name', $data)) {
            $name = ValueRules::cleanText($data['name']);
            if ($name === '' || mb_strlen($name) > 200) {
                return ['status' => false, 'message' => 'Nama produk wajib diisi dan maksimal 200 karakter'];
            }
        }

        if (array_key_exists('sku_code', $data)) {
            $sku = ValueRules::cleanText($data['sku_code']);
            if ($sku === '' || ValueRules::normalizeCode($sku, 50) === null) {
                return ['status' => false, 'message' => 'SKU code hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung (maksimal 50 karakter)'];
            }
            $existingSku = $this->db->table('products')
                ->where('sku_code', $sku)
                ->where('id_product !=', $id)
                ->countAllResults();
            if ($existingSku > 0) {
                return [
                    'status' => false,
                    'message' => 'SKU code sudah digunakan'
                ];
            }
        }

        if (array_key_exists('brand_name', $data)) {
            $brandName = MasterInput::cleanOptional($data['brand_name']);
            if ($brandName === false || ($brandName !== null && mb_strlen($brandName) > 200)) {
                return ['status' => false, 'message' => 'Brand maksimal 200 karakter'];
            }
        }

        if (array_key_exists('unit', $data)) {
            $unit = ValueRules::cleanText($data['unit']);
            if ($unit === '' || mb_strlen($unit) > 50) {
                return ['status' => false, 'message' => 'Satuan wajib diisi dan maksimal 50 karakter'];
            }
        }

        if (array_key_exists('status', $data)) {
            $status = strtoupper(trim((string) $data['status']));
            if (!in_array($status, [WmsPolicy::STATUS_ACTIVE, 'NONAKTIF'], true)) {
                return ['status' => false, 'message' => 'Status produk tidak valid'];
            }
        }

        if (array_key_exists('id_category', $data)) {
            $categoryId = ValueRules::positiveId($data['id_category']);
            $categoryExists = $categoryId === null ? 0 : $this->db->table('categories')
                ->where('id_category', $categoryId)
                ->where('status', WmsPolicy::STATUS_ACTIVE)
                ->countAllResults();
            if ($categoryExists === 0) {
                return ['status' => false, 'message' => 'Kategori tidak ditemukan / tidak aktif'];
            }
        }

        if (array_key_exists('id_supplier', $data)) {
            $supplierId = ValueRules::positiveId($data['id_supplier']);
            $supplierExists = $supplierId === null ? 0 : $this->db->table('suppliers')
                ->where('id_supplier', $supplierId)
                ->where('status', WmsPolicy::STATUS_ACTIVE)
                ->countAllResults();
            if ($supplierExists === 0) {
                return ['status' => false, 'message' => 'Supplier tidak ditemukan / tidak aktif'];
            }
        }

        $minimumStock = null;
        if (array_key_exists('minimum_stock', $data)) {
            $minimumStock = MasterInput::parseInteger($data['minimum_stock'], 0, 2147483647);
            if ($minimumStock === null) {
                return [
                    'status' => false,
                    'message' => 'Minimum stock harus bilangan bulat antara 0 dan 2147483647'
                ];
            }
        }

        $productData = [
            'name'           => $name,
            'sku_code'       => $sku,
            'id_supplier'    => $supplierId,
            'id_category'    => $categoryId,
            'brand_name'     => $brandName,
            'unit'           => $unit,
            'minimum_stock'  => $minimumStock,
            'status'         => $status,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($categoryId !== null) {
            $productData['category'] = $this->categoryName($categoryId);
        }

        $productData = array_filter($productData, fn($v) => $v !== null);

        $this->db->table('products')->where('id_product', $id)->update($productData);

        if ($this->db->affectedRows() > 0) {
            return [
                'status' => true,
                'message' => 'Produk berhasil diupdate'
            ];
        }

        return [
            'status' => false,
            'message' => 'Tidak ada perubahan pada produk'
        ];
    }

    public function deleteProduct(string $id): array
    {
        $batchRows = $this->db
            ->table('product_batches')
            ->select('id_batch')
            ->where('id_product', $id)
            ->get()
            ->getResult();

        $batchIds = array_map(static fn ($row) => $row->id_batch, $batchRows);

        $inboundDetails = $this->db->table('inbound_details')->where('id_product', $id)->countAllResults();
        $outboundDetails = 0;
        $opnameDetails = 0;
        $adjustments = 0;

        if ($batchIds !== []) {
            $outboundDetails = $this->db->table('outbound_details')->whereIn('id_batch', $batchIds)->countAllResults();
            $opnameDetails = $this->db->table('stock_opname_details')->whereIn('id_batch', $batchIds)->countAllResults();
            $adjustments = $this->db->table('stock_adjustments')->whereIn('id_batch', $batchIds)->countAllResults();
        }

        if (count($batchRows) > 0 || $inboundDetails > 0 || $outboundDetails > 0 || $opnameDetails > 0 || $adjustments > 0) {
            return [
                'status' => false,
                'message' => 'Produk tidak bisa dihapus karena masih memiliki batch stok atau riwayat transaksi. Nonaktifkan produk saja.'
            ];
        }

        $this->db->table('products')->where('id_product', $id)->delete();

        if ($this->db->affectedRows() > 0) {
            return [
                'status' => true,
                'message' => 'Produk berhasil dihapus'
            ];
        }

        return [
            'status' => false,
            'message' => 'Gagal menghapus produk'
        ];
    }
}

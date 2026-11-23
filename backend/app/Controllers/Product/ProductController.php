<?php

namespace App\Controllers\Product;

use App\Controllers\BaseController;
use App\Services\ProductService;

class ProductController extends BaseController
{
    protected ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function statistic()
    {
        try {
            return $this->response->setJSON(
                $this->productService->getStatistic()
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat statistik produk'
                ]);
        }
    }

    public function index()
    {
        try {
            $supplierId = $this->request->getGet('supplier_id');
            $search = $this->request->getGet('search');
            return $this->response->setJSON(
                $this->productService->getProducts($supplierId, $search)
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat data produk'
                ]);
        }
    }

    public function show($id)
    {
        try {
            $result = $this->productService->getProductById($id);
            $code = $result['status'] ? 200 : 404;
            return $this->response->setStatusCode($code)->setJSON($result);
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memuat data produk'
                ]);
        }
    }

    public function store()
    {
        $this->denyIfNotAdmin();
        try {
            $data = $this->request->getJSON(true);

            $required = ['name', 'sku_code', 'id_category', 'id_supplier'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    return $this->response->setJSON([
                        'status' => false,
                        'message' => "Field {$field} wajib diisi"
                    ])->setStatusCode(400);
                }
            }

            $result = $this->productService->createProduct($data);
            $code = $result['status'] ? 201 : 400;
            return $this->response->setJSON($result)->setStatusCode($code);
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Terjadi kesalahan pada server'
                ]);
        }
    }

    public function update($id)
    {
        $this->denyIfNotAdmin();
        try {
            $existing = $this->productService->getProductById($id);
            if (!$existing['status']) {
                return $this->response->setJSON($existing)->setStatusCode(404);
            }

            $data = $this->request->getJSON(true);
            $result = $this->productService->updateProduct($id, $data);
            $code = $result['status'] ? 200 : 400;
            return $this->response->setJSON($result)->setStatusCode($code);
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Terjadi kesalahan pada server'
                ]);
        }
    }

    public function delete($id)
    {
        $this->denyIfNotAdmin();
        try {
            $existing = $this->productService->getProductById($id);
            if (!$existing['status']) {
                return $this->response->setJSON($existing)->setStatusCode(404);
            }

            $result = $this->productService->deleteProduct($id);
            $code = $result['status'] ? 200 : 400;
            return $this->response->setJSON($result)->setStatusCode($code);
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Terjadi kesalahan pada server'
                ]);
        }
    }
}

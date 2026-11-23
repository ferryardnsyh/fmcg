<?php

namespace App\Controllers\Api;

use App\Services\Master\CategoryService;

class CategoryController extends ApiController
{
    protected CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }

    public function index()
    {
        try {
            return $this->respondServiceResultWithCode(
                $this->categoryService->getCategories($this->request->getGet()),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data kategori', 500);
        }
    }

    public function all()
    {
        try {
            return $this->respondServiceResultWithCode($this->categoryService->getAllCategories(), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data kategori', 500);
        }
    }

    public function show($id)
    {
        try {
            return $this->respondServiceResultWithCode($this->categoryService->getCategory($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat detail kategori', 500);
        }
    }

    public function nextCode()
    {
        try {
            return $this->respondServiceResultWithCode($this->categoryService->nextCode(), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal generate kode kategori', 500);
        }
    }

    public function create()
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode(
                $this->categoryService->createCategory($this->request->getJSON(true)),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            if ($this->categoryService->isDuplicateCodeConflict($e)) {
                return $this->respondException($e, 'Kode kategori sudah digunakan', 409);
            }
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }

    public function update($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode(
                $this->categoryService->updateCategory($id, $this->request->getJSON(true)),
                200,
                400,
                true
            );
        } catch (\Throwable $e) {
            if ($this->categoryService->isDuplicateCodeConflict($e)) {
                return $this->respondException($e, 'Kode kategori sudah digunakan', 409);
            }
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }

    public function delete($id)
    {
        $this->denyIfNotAdmin();
        try {
            return $this->respondServiceResultWithCode($this->categoryService->deleteCategory($id), 200, 400, true);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server', 500);
        }
    }
}

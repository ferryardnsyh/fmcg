<?php

namespace App\Services\Master;

use Config\Database;

class MasterDataService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function categories(): array
    {
        return $this->db->table('categories')
            ->select('id_category, category_code, name')
            ->where('status', 'AKTIF')
            ->orderBy('id_category', 'ASC')
            ->get()->getResultArray();
    }

    public function suppliers(): array
    {
        return $this->db->table('suppliers')
            ->select('id_supplier, name')
            ->where('status', 'AKTIF')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    public function rackLocations(): array
    {
        return $this->db->table('rack')
            ->select('id_rack, name, rack_code')
            ->where('status', 'AKTIF')
            ->orderBy('rack_code', 'ASC')
            ->get()->getResultArray();
    }
}

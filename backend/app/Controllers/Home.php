<?php

namespace App\Controllers;

use Config\Database;

class Home extends BaseController
{
    public function index()
    {
        try {
            $db = Database::connect();
            $db->query("SELECT 1");

            return "Koneksi Database Berhasil";
        } catch (\Exception $e) {
            return "Koneksi Database Gagal";
        }
    }
}

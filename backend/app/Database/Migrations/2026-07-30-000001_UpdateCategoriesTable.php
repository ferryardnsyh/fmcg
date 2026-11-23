<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateCategoriesTable extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE categories ADD COLUMN code VARCHAR(10) NULL AFTER id");
        $this->db->query("ALTER TABLE categories ADD COLUMN description TEXT NULL AFTER name");
        $this->db->query("ALTER TABLE categories ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description");
        $this->db->query("ALTER TABLE categories ADD COLUMN updated_at DATETIME NULL AFTER created_at");

        $existing = $this->db->query("SELECT COUNT(*) AS cnt FROM categories WHERE code IS NOT NULL")->getRowArray();
        if ((int)$existing['cnt'] === 0) {
            $rows = $this->db->query("SELECT id FROM categories ORDER BY id")->getResultArray();
            $num = 1;
            foreach ($rows as $row) {
                $code = 'CAT' . str_pad($num, 3, '0', STR_PAD_LEFT);
                $this->db->query("UPDATE categories SET code = '{$code}' WHERE id = " . (int)$row['id']);
                $num++;
            }
        }
    }

    public function down()
    {
        $this->db->query("ALTER TABLE categories DROP COLUMN code");
        $this->db->query("ALTER TABLE categories DROP COLUMN description");
        $this->db->query("ALTER TABLE categories DROP COLUMN is_active");
        $this->db->query("ALTER TABLE categories DROP COLUMN updated_at");
    }
}

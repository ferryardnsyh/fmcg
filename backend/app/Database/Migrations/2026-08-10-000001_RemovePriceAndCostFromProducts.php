<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePriceAndCostFromProducts extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE products DROP COLUMN price");
        $this->db->query("ALTER TABLE products DROP COLUMN cost");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE products ADD COLUMN price DECIMAL(15,2) NULL DEFAULT 0.00");
        $this->db->query("ALTER TABLE products ADD COLUMN cost DECIMAL(15,2) NULL DEFAULT 0.00");
    }
}

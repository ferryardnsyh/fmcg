<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestrictBatchHistoryForeignKeys extends Migration
{
    private array $constraints = [
        'inbound_details'      => 'fk_inbound_detail_batch',
        'outbound_details'     => 'fk_outbound_detail_batch',
        'stock_opname_details' => 'fk_opname_detail_batch',
        'stock_adjustments'    => 'fk_adjustment_batch',
    ];

    public function up()
    {
        foreach ($this->constraints as $table => $constraint) {
            $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
            $this->db->query("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch) ON DELETE RESTRICT ON UPDATE CASCADE");
        }
    }

    public function down()
    {
        foreach ($this->constraints as $table => $constraint) {
            $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
            $this->db->query("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch) ON DELETE SET NULL ON UPDATE CASCADE");
        }
    }
}

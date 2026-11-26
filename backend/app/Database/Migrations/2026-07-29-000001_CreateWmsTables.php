<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWmsTables extends Migration
{
    public function up()
    {
        // ========== INBOUND HEADERS ==========
        $this->forge->addField([
            'id'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'reception_number'=> ['type' => 'VARCHAR', 'constraint' => 50],
            'supplier_id'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'reception_date'  => ['type' => 'DATE'],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'created_by'      => ['type' => 'INT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('inbound');

        // ========== INBOUND DETAILS ==========
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'inbound_header_id'=> ['type' => 'VARCHAR', 'constraint' => 20],
            'product_id'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'batch_id'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'quantity'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'rack_location_id' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('inbound_details');

        // ========== OUTBOUND HEADERS ==========
        $this->forge->addField([
            'id'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'delivery_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'delivery_date'   => ['type' => 'DATE'],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'created_by'      => ['type' => 'INT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('outbound');

        // ========== OUTBOUND DETAILS ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'outbound_header_id'=> ['type' => 'VARCHAR', 'constraint' => 20],
            'product_id'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'batch_id'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'quantity'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('outbound_details');

        // ========== STOCK ADJUSTMENTS ==========
        $this->forge->addField([
            'id'               => ['type' => 'VARCHAR', 'constraint' => 20],
            'adjustment_number'=> ['type' => 'VARCHAR', 'constraint' => 50],
            'product_id'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'batch_id'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'type'             => ['type' => 'VARCHAR', 'constraint' => 10],
            'quantity_before'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'quantity_after'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'reason'           => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING'],
            'opname_id'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'approved_by'      => ['type' => 'INT', 'null' => true],
            'approved_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_by'       => ['type' => 'INT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stock_adjustments');

        // ========== STOCK OPNAME SESSIONS ==========
        $this->forge->addField([
            'id'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'session_number'=> ['type' => 'VARCHAR', 'constraint' => 50],
            'session_date'  => ['type' => 'DATE'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_by'    => ['type' => 'INT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stock_opname_sessions');

        // ========== STOCK OPNAME DETAILS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'session_id'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'product_id'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'batch_id'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'system_quantity' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'physical_quantity'=> ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'difference'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stock_opname_details');
    }

    public function down()
    {
        $this->forge->dropTable('stock_opname_details');
        $this->forge->dropTable('stock_opname_sessions');
        $this->forge->dropTable('stock_adjustments');
        $this->forge->dropTable('outbound_details');
        $this->forge->dropTable('outbound');
        $this->forge->dropTable('inbound_details');
        $this->forge->dropTable('inbound');
    }
}

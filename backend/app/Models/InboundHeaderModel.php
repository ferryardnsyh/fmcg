<?php

namespace App\Models;

use CodeIgniter\Model;

class InboundHeaderModel extends Model
{
    protected $table = 'inbound_headers';

    protected $primaryKey = 'id_inbound';

    protected $returnType = 'array';

    protected $allowedFields = [
        'reception_number',
        'id_supplier',
        'reception_date',
        'po_number',
        'delivery_note_number',
        'notes',
        'status',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}

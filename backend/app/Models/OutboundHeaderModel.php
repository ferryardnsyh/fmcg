<?php

namespace App\Models;

use CodeIgniter\Model;

class OutboundHeaderModel extends Model
{
    protected $table = 'outbound_headers';

    protected $primaryKey = 'id_outbound';

    protected $returnType = 'array';

    protected $allowedFields = [
        'delivery_number',
        'delivery_date',
        'notes',
        'status',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}

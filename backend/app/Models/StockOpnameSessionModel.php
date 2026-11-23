<?php

namespace App\Models;

use CodeIgniter\Model;

class StockOpnameSessionModel extends Model
{
    protected $table = 'stock_opname_sessions';

    protected $primaryKey = 'id_opname';

    protected $returnType = 'array';

    protected $allowedFields = [
        'opname_number',
        'opname_date',
        'status',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class StockOpnameDetailModel extends Model
{
    protected $table = 'stock_opname_details';

    protected $primaryKey = 'id_opname_detail';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'id_opname',
        'id_batch',
        'system_quantity',
        'physical_quantity',
        'difference'
    ];

    protected $useTimestamps = false;
}

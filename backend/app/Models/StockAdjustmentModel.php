<?php

namespace App\Models;

use CodeIgniter\Model;

class StockAdjustmentModel extends Model
{
    protected $table = 'stock_adjustments';

    protected $primaryKey = 'id_adjustment';

    protected $returnType = 'array';

    protected $allowedFields = [
        'id_batch',
        'type',
        'quantity',
        'reason',
        'status',
        'approved_by',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}

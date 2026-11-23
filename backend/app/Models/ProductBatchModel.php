<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductBatchModel extends Model
{
    protected $table = 'product_batches';

    protected $primaryKey = 'id_batch';

    protected $returnType = 'array';

    protected $allowedFields = [
        'id_product',
        'batch_number',
        'expiry_date',
        'quantity',
        'id_rack',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id_product';

    protected $returnType = 'array';

    protected $allowedFields = [
        'sku_code',
        'name',
        'category',
        'id_supplier',
        'id_category',
        'brand_name',
        'unit',
        'minimum_stock',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}

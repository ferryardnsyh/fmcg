<?php

namespace App\Models;

use CodeIgniter\Model;

class InboundDetailModel extends Model
{
    protected $table = 'inbound_details';

    protected $primaryKey = 'id_inbound_detail';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'id_inbound',
        'id_product',
        'batch_number',
        'id_batch',
        'quantity',
        'expiry_date',
        'id_rack'
    ];

    protected $useTimestamps = false;
}

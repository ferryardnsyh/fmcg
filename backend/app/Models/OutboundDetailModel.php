<?php

namespace App\Models;

use CodeIgniter\Model;

class OutboundDetailModel extends Model
{
    protected $table = 'outbound_details';

    protected $primaryKey = 'id_outbound_detail';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'id_outbound',
        'id_batch',
        'quantity'
    ];

    protected $useTimestamps = false;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class RackLocationModel extends Model
{
    protected $table = 'rack_locations';

    protected $primaryKey = 'id_rack';

    protected $returnType = 'array';

    protected $allowedFields = [
        'rack_code',
        'name',
        'status'
    ];

    protected $useTimestamps = false;
}

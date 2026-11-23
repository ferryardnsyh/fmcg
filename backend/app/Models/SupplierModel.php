<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierModel extends Model
{
    protected $table = 'suppliers';

    protected $primaryKey = 'id_supplier';

    protected $returnType = 'array';

    protected $allowedFields = [
        'supplier_code',
        'name',
        'phone',
        'address',
        'status'
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'supplier_code' => 'required|trim|max_length[50]',
        'name'          => 'required|trim|max_length[200]',
        'phone'         => 'permit_empty|trim|max_length[50]',
        'address'       => 'permit_empty|trim',
        'status'        => 'required|in_list[AKTIF,NONAKTIF]',
    ];
}

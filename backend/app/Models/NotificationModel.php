<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'type', 'priority', 'title', 'message',
        'reference_type', 'reference_id',
        'user_role', 'is_read'
    ];
    protected $useTimestamps = false;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table            = 'bookings';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'user_id',
        'kost_id',
        'occupant_count',
        'campus_name',
        'identity_doc',
        'status',
        'rejection_note',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps    = true;
}

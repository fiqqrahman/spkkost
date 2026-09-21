<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'booking_id',
        'amount',
        'proof_image',
        'payment_date',
        'status',
        'note',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps    = true;
}

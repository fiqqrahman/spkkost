<?php

namespace App\Models;

use CodeIgniter\Model;

class CampusModel extends Model
{
    protected $table            = 'campuses';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'latitude', 'longitude'];
}

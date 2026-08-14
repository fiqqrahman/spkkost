<?php

namespace App\Models;

use CodeIgniter\Model;

class CriteriaModel extends Model
{
    protected $table            = 'criterias';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['code', 'name', 'type', 'weight'];
}

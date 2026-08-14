<?php

namespace App\Models;

use CodeIgniter\Model;

class FeatureModel extends Model
{
    protected $table            = 'features';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['criteria_id', 'name', 'point'];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    protected $table = 'LOCALIDADES';
    protected $primaryKey = 'localidad_id';
    public $timestamps = false;
}

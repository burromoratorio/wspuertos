<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'CLIENTES';
    protected $primaryKey = 'cliente_id';
    public $timestamps = false;
}

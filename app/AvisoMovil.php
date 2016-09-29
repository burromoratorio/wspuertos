<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvisoMovil extends Model
{
    protected $table = 'avisos_moviles';
    protected $fillable = ['evento_tipo_id', 'movil_id', 'eventable_id', 'eventable_type'];
    protected $dateFormat = 'Y-m-d H:i:s';
}

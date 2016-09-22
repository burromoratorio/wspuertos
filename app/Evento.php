<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = ['evento_tipo_id', 'movil_id', 'eventable_id', 'eventable_type'];
}

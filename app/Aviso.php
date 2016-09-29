<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    protected $table = 'avisos';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = ['aviso_cliente_id', 'estado_envio_id', 'aviso'];
}

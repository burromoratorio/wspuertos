<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvisoCliente extends Model
{
    protected $table = 'avisos_clientes';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = ['aviso_tipo_id', 'cliente_id'];
}

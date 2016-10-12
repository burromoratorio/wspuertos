<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvisoConfiguracion extends Model
{
    protected $table = 'avisos_configuraciones';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = ['aviso_cliente_id', 'aviso_configuracion_tipo_id', 'valor'];
}

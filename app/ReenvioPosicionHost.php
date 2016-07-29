<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReenvioPosicionHost extends Model
{
    protected $table = 'reenvios_posiciones_hosts';
    protected $fillable = ['reenvio_posicion_id', 'reenvio_host_id', 'estado_envio_id'];
    protected $dateFormat = 'Y-m-d H:i:s';

    public function reenvio_posicion() {
        return $this->belongsTo('App\ReenvioPosicion');
    }

    public function reenvio_host() {
        return $this->belongsTo('App\ReenvioHost');
    }

    public function estado_envio() {
        return $this->belongsTo('App\EstadoEnvio');
    }
}

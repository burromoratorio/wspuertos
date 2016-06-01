<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReenvioPosicion extends Model
{
    protected $table = 'reenvios_posiciones';
    protected $fillable = ['movil_id', 'reenvio_host_id', 'estado_envio_id', 'cadena'];

    public function reenvio_host(){
        return $this->belongsTo('App\ReenvioHost');
    }

    public function movil(){
        return $this->belongsTo('App\Movil');
    }

    public function estado_envio(){
        return $this->belongsTo('App\EstadoEnvio');
    }
}

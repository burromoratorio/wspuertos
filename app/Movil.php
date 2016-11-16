<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Movil extends Model
{
    protected $table = 'MOVILES';
    protected $primaryKey = 'movil_id';
    public $timestamps = false;

    public function posiciones() {
        return $this->hasMany('App\Posicion');
    }

    public function cliente() {
        return $this->belongsTo('App\Cliente');
    }

    public function reenvio_movil() {
        return $this->hasOne('App\ReenvioMovil');
    }
}

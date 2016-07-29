<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReenvioMovil extends Model
{
    protected $table = 'reenvios_moviles';

    protected $guarded = ['id'];

    public function reenvio_host() {
        return $this->belongsTo('App\ReenvioHost');
    }

    public function movil() {
        return $this->belongsTo('App\Movil');
    }

    public function usuario() {
        return $this->belongsTo('App\Usuario');
    }
}

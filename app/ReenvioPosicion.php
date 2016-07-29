<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReenvioPosicion extends Model
{
    protected $table = 'reenvios_posiciones';
    protected $fillable = ['movil_id', 'cadena'];
    protected $dateFormat = 'Y-m-d H:i:s';

    public function movil() {
        return $this->belongsTo('App\Movil');
    }
    public function reenvios_moviles() {
        return $this->hasMany('App\ReenvioMovil', 'movil_id', 'movil_id');
    }
}

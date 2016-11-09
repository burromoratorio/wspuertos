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
    static function viajesAbiertos($dominio){
        return Movil::wherehas('viajes',function($query){ 
                $query->select('viaje_id','movil_id')
                ->whereNull('fecha_fin'); })
                ->where('dominio',$dominio)->first();
    }
    public function viajes() {
        return $this->hasMany('App\Viaje');
    }
    public function instalacion() {
        return $this->hasOne('App\Instalacion');
    }
}

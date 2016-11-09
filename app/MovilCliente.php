<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovilCliente extends Model
{
    protected $table = 'MOVILES_CLIENTES';
    protected $primaryKey = 'movil_id,cliente_id';
    public $timestamps = false;

    public function cliente() {
        return $this->belongsTo('App\Cliente');
    }
    public function moviles($cliente_id) {
        return MovilCliente::where('cliente_id',$cliente_id)->get();
    }
    /*static function viajesAbiertos($dominio){
        return Movil::wherehas('viajes',function($query){ 
                $query->select('viaje_id','movil_id')
                ->whereNull('fecha_fin'); })
                ->where('dominio',$dominio)->first();
    }*/
}

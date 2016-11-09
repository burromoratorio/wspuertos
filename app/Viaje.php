<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MovilCliente;
use DB;
class Viaje extends Model
{
    protected $table 		= 'viajes';
    protected $primaryKey 	= 'viaje_id';
    public $timestamps 		= false;
    protected $fillable 	= ['waypoint_id', 'tipo_carga_id', 'movil_id', 'estado', 'tipo_viaje_id','nro_viaje',
    						'fecha_aviso','fecha_inicio', 'cliente_id', 'nro_viaje', 'km_salida', 'usuario_id',
                            'dominio_semi','cant_clientes','usuario_id','dominio_semi_sec'];
    public function entregasViaje() {
       	return $this->hasMany('App\EntregaViaje');
    }
  	public function movil() {
        return $this->belongsTo('App\Movil');
    }
    static public function viajesActivosCliente($cliente,$tipo_viaje_id){
        return Viaje::whereIn('movil_id', MovilCliente::select('movil_id')->where('cliente_id',$cliente)->orderBy('movil_id')->get())
                        ->where('tipo_viaje_id','=',$tipo_viaje_id)
                        ->where('estado', '<>' ,'4')
                        ->orderBy('fecha_inicio','asc')->get();
         /*return Viaje::where('estado', '<>' ,'4')
                            ->where('tipo_viaje_id','=',$tipo_viaje_id)
                            ->orderBy('fecha_inicio','asc')->get();*/
        /*return  Viaje::wherehas('movil',function($query)use ($cliente){ 
                    $query->select('viaje_id','movil_id')->where('cliente_id','=',$cliente)
                    ->orWhere('fletero_id','=',$cliente); })
                    ->orWhere('cliente_id','=',$cliente)
                    ->where('tipo_viaje_id','=',$tipo_viaje_id)
                    ->where('estado', '<>' ,'4')->orderBy('fecha_inicio','asc')->get();*/
       /* return Viaje::wherehas('movil',function($query) use ($cliente,$tipo_viaje_id){
            $query->select('viaje_id','movil_id')->where('cliente_id','=',$cliente)->orWhere('fletero_id','=',$cliente); })
            ->where('tipo_viaje_id','=',$tipo_viaje_id)->where('estado', '<>' ,'4')
            ->orWhere(function ($q) use ($cliente,$tipo_viaje_id) {
            $q->where('tipo_viaje_id','=',$tipo_viaje_id)->where('estado', '<>' ,'4')->where('cliente_id','=',$cliente);})
            ->orderBy('fecha_inicio','asc')->get();*/

    }
}

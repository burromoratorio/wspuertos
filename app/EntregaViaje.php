<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EntregaViaje extends Model
{
    protected $table 		= 'entregas_viajes';
    protected $primaryKey 	= 'entrega_viaje_id';
    public $timestamps 		= false;
    protected $fillable 	= ['waypoint_id', 'orden', 'tipo_remito_id','fecha_libre'];
    public function waypoint() {
       	return $this->hasOne('App\Waypoint');
    }
    public function producto() {
        return $this->hasOne('App\Producto');
    }
    public function carga() {
        return $this->hasOne('App\Carga');
    }
}

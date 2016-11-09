<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Carga extends Model
{
    protected $table 		= 'cargas';
    protected $primaryKey 	= 'carga_id';
    public $timestamps 		= false;
    protected $fillable 	= ['producto_id', 'volumen', 'unidad'];
    public function entregaViaje() {
       	return $this->belongsTo('App\EntregaViaje');
    }
    public function producto() {
        return $this->hasOne('App\Producto');
    }

}

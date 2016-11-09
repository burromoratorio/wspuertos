<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Posiciones extends Model
{
    protected $table = 'POSICIONES_HISTORICAS';
    public $timestamps = false;
    protected $connection = 'siac';
    protected $primaryKey = ['posicion_id'];    
    protected $fillable = array('movil_id', 'fecha', 'velocidad','latitud','longitud','valida','km_recorridos','referencia');

    public function movil() {
        return $this->belongsTo('App\Movil');
    }
    public function getAttribute($value)
    {
        return $this->attributes[$value];
    }
   
}

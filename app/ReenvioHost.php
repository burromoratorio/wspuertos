<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReenvioHost extends Model
{
    protected $table = 'REENVIOS_HOSTS';
    public $timestamps = false;

    protected $guarded = ['id'];    
    protected $fillable = array('nombre', 'destino', 'puerto');

    public function reenvios_moviles() {
        return $this->hasMany('App\ReenvioMovil');
    }

    public function reenvios_posiciones() {
        return $this->hasMany('App\ReenvioPosicion');
    }
}

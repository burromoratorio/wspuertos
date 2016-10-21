<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Destinatario extends Model
{
    protected $table = 'destinatarios';

    protected $guarded = ['id'];
    protected $fillable = array('cliente_id', 'mail');

    protected $dates = ['created_at','updated_at'];
    protected $dateFormat = 'Y-m-d H:i:s';

    public function clientes() {
        return $this->belongsTo('App\Cliente', 'cliente_id');
    }

}

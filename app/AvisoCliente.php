<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvisoCliente extends Model
{
    protected $table = 'avisos_clientes';
    protected $fillable = ['aviso_tipo_id', 'cliente_id'];
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $dates = ['created_at','updated_at'];
    
    public function cliente(){
        return $this->belongsTo('App\Cliente', 'cliente_id', 'cliente_id');//->select(['cliente_id', 'razon_social']);

    }
}

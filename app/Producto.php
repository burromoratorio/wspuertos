<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table 		= 'productos';
    protected $primaryKey 	= 'producto_id';
    public $timestamps 		= false;
    
    public function cliente() {
        return $this->belongsTo('App\Cliente');
    }
 	
}

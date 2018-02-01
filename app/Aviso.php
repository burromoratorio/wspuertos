<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    protected $table = 'avisos';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = ['aviso_cliente_id', 'estado_envio_id', 'aviso'];
    
    
    public function avisoCliente(){
        return $this->belongsTo('App\AvisoCliente', 'aviso_cliente_id', 'id');
    }
    public function estado(){
        return $this->belongsTo('App\EstadoEnvio', 'estado_envio_id', 'id');
    }
    public static function getAvisos(){
		//return Aviso::with('AvisoCliente','AvisoCliente.cliente','estado')->get();
		return Aviso::with(['avisoCliente.cliente'=>function($cliente){
							$cliente->select('cliente_id','razon_social');
						},
						'estado'=>function($estado){
							$estado->select('id','estado');
						}
						])->orderBy('id','DESC')->get();
		
		
		/*return Aviso::with(['avisoCliente' => function($query){
                    $query->with(['cliente'=>function($detail){
                        // selecting fields from authordetail table
                        $detail->select('clientes.razon_social'); 
                    }]);
                    },'estado'=>function($estado){
						// selecting fields from  tags table
						$estado->select('estados_envios.estado'); 
					}
					])
					->orderBy('id','DESC')
					// selecting fields from post table
					->select('avisos.id','avisos.aviso')->get();*/
					
					
					
		
	}
}

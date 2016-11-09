<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Viaje;
use App\Movil;
use App\Producto;
use App\Waypoint;
use App\EntregaViaje;
use App\Carga;
use App\Usuario;
use App\Posiciones;
Use App\Instalacion;
Use Log;
use stdClass;
use Storage;
use DB;
class ViajeController extends Controller
{
    public function create(Request $request){
	$cadenaEnvio="";
    	$method = $request->method();
    	if ($request->isMethod('post')) {
			$jsonReq = $request->json()->all();
			$coleccionViajes	= $jsonReq["Viajes"];
			//print_r($coleccionViajes);
			$i=0;
			$viajesEnvio	= array();
			$viajes=[];
			foreach ($coleccionViajes as $viaje) {
					//si el movil tiene un viaje abierto ->lo obtengo
					$movil 				= Movil::where('dominio', $viaje['dominio'])->first();
					$movilViajeAbierto 	= Movil::viajesAbiertos($viaje['dominio']);
					if(count($movilViajeAbierto)>0){
						//busco el viaje abierto de ese movil y lo cierro
						$viajeCerrar	= $this->cerrarViajeMovil($movilViajeAbierto);
					}
					$entregas = [];
		            //una vez validado el estado de los viajes...
					// existe producto?existe el Waypoint?
					foreach ($viaje['waypoints'] as $k=>$entregaV) {
						$this->validaProducto($entregaV['producto'],$viaje['codigo_cliente']);
						$this->validaWaypoint($entregaV['cod_dir_envio'],$viaje['codigo_cliente']); 
						//$orden = 0;
						$waypointSalida	=Waypoint::select('waypoint_id')
			            								->where('codigo_waypoint',$entregaV['cod_dir_envio'])
                        								->where('cliente_id',$viaje['codigo_cliente'])->first();
						if($entregaV['orden_de_reparto']=="0"){
							$fechaSalida		= $viaje['fecha_partida'];
							$fechaInicioViaje	= $viaje['fecha_partida'];
							$waypointInicioViaje=$waypointSalida->waypoint_id;
						}else{
							$fechaSalida	= null;
						}

			            $entrega = ($entregas[] = new stdClass);

			            $entrega->model = new EntregaViaje(['waypoint_id'=>$waypointSalida->waypoint_id,
			            									'orden' 	  => $entregaV['orden_de_reparto'],
			            								    'tipo_remito_id' => 1,
			            								    'fecha_libre'=>$fechaSalida,
			            									]);
			            $producto=Producto::select('producto_id')
			            									->where('cod_producto',$entregaV['producto'])
                        									->where('cliente_id',$viaje['codigo_cliente'])->first();
			            $entrega->carga = new Carga(['producto_id'=>$producto->producto_id,
                        							'volumen' => $entregaV['volumen'],
                        							'unidad' => $entregaV['unidad'],
                    								]);
			           
					}
		            Log::debug("Se creara viaje Nº".$viaje['nro_guia']);
		            Log::debug("Se daran de alta ".count($entregas)." entregas");
		            foreach ($entregas as $entrega) {
		                Log::debug("Entrega orden: ".$entrega->model->orden);
		                Log::debug("Carga numero: ".$entrega->carga->producto_id);
		            }
		            /*Rta al cliente*/
		           	$viajeDb=new Viaje();
		            $viajeDb->waypoint_id=$waypointSalida->waypoint_id;
		            $viajeDb->tipo_carga_id= 1;//default
		            $viajeDb->movil_id=$movil->movil_id;
		            $viajeDb->estado= 1;//en origen
		            $viajeDb->tipo_viaje_id= 1;//regular
		            $viajeDb->fecha_aviso=$fechaInicioViaje;
		            $viajeDb->fecha_inicio=$fechaInicioViaje;
		            $viajeDb->cliente_id= $viaje['codigo_cliente'];
		            $viajeDb->nro_viaje= $viaje['nro_guia'];
		            $usuario 	= Usuario::select('usuario_id')->where('cliente_id',$viaje['codigo_cliente'])->first();
		            $viajeDb->usuario_id=$usuario->usuario_id; 
		            $viajeDb->entregasViaje=$entregas;
		            
		            $usuario 	= Usuario::select('usuario_id')->where('cliente_id',$viaje['codigo_cliente'])->first();
		            DB::beginTransaction();
		            //tipo_viaje_id->1=en_origen,2=en_viaje,3=en_destino,4=finalizado
		            try {
		                $viajeDb = Viaje::create([
		                    'waypoint_id' => $waypointInicioViaje,
		                    'tipo_carga_id' => 1,//default
		                    'movil_id' => $movil->movil_id,
		                    'estado' => 1,//en origen
		                    'tipo_viaje_id' => 1,//regular
		                    'fecha_aviso' => $fechaInicioViaje,
		                    'fecha_inicio' => $fechaInicioViaje,
		                    'cliente_id' => $viaje['codigo_cliente'],
		                    'nro_viaje' => $viaje['nro_guia'],
		                    'usuario_id' => $usuario->usuario_id,
		                    'dominio_semi'=>$viaje['dominio_acoplado'], 
		                    'dominio_semi_sec'=>$viaje['dominio_acoplado_sec'], 
		                    'cant_clientes'=>count($entregas)-1,
		                    'usuario_id'=>6, //sistema,
		                    'km_salida'=>$viaje['km_salida'], 


		                ]);
		            foreach ($entregas as $entrega) $viajeDb->entregasViaje()->save($entrega->model)->carga()->save($entrega->carga);
		                DB::commit();
		            } catch (\Exception $ex) {
		                DB::rollBack();
		                Log::error("Algo anduvo mal. Revisar");
		                Log::error($ex);
		                $response = ["viaje" => 'no se pudo insertar el viaje, pongase en contacto con nosotros'];
		            }
		            $viajes[]	= $viajeDb;
			$entregasMail	= count($entregas)-1;
			$cadenaEnvio=" Movil:".$viaje['dominio']." fecha de salida:".$fechaInicioViaje." Cantidad de Clientes:".$entregasMail." Nro Guia:".$viaje['nro_guia']." Acoplado:".$viaje['dominio_acoplado']." Acoplado Secundario:".$viaje['dominio_acoplado_sec'] ;
			$this->enviarMail("Nuevo viaje Ingresado",$cadenaEnvio,"amoratorio@siacseguridad.com");
			}
			$response = ["viajes" =>$viajes ];
		}else{
			$response = ["viaje" => 'no se pudo insertar el viaje, pongase en contacto con nosotros'];
		}
    	return $response;
    }
    public function getViajes(Request $request, $id,$cliente){
    	$estadoViajes  = ["1"=>"En Origen","2"=>"En Viaje","3"=>"En Destino","4"=>"Finalizado"];
    	$codigoCliente = 0;
    	if($id==0){
    		//Log::debug("todos del cliente:".$cliente." viaje:".$id);
    		/*mov.cliente_id=1285 OR mov.fletero_id=1285 OR  MOVILES_CLIENTES.cliente_id = 1285
    		$movilViajeAbierto 	= Movil::viajesAbiertos($viaje['dominio']);*/
    		$austin = array(441,799,807,812,816,817,818,820,821,822,1603,1774,2185);
    		$clientes= array(1285,973);
			if (in_array($cliente, $austin)){
			  $codigoCliente=666;
			}
			if (in_array($cliente, $clientes)){
			  $codigoCliente=$cliente;
			}
    		$tipoViaje=[0=>"1",1285=>"2",666=>"3",973=>"4"];
    		$viajes = Viaje::viajesActivosCliente($cliente,$tipoViaje[$codigoCliente]);
    	}else{
    		try{
			    $viajes = Viaje::where('nro_plan_carga', '=' ,$id)->get();
			}catch(ModelNotFoundException $e){
			    dd(get_class_methods($e)); // lists all available methods for exception object
			    dd($e);
			}
			
    	}
    	foreach ($viajes as $viaje) {
			$movil = $viaje->movil;
		    $equipoId	= $movil->instalacion->equipo_id;
		    //consultas cambiando conexion a siac
		    if(DB::connection()->getDatabaseName()=='moviles'){
		    	config()->set('database.default', 'siac');
		    	$posModel 	= new Posiciones;
	        	$posModel->setConnection('siac');
	        	$posicion 	=array();
	        	$posInicioViaje=array();
	        	$movilID 	= Movil::select('movil_id')->where('modem_id',$equipoId)->first();
	           	$posActual	= Posiciones::where('movil_id',$movilID->movil_id)->orderBy('fecha', 'desc')->first();
	           	//DB::select('select TOP 1 * from POSICIONES_HISTORICAS where movil_id =', $movilID->movil_id. ' order by fecha desc');
	           	if(!is_null($posActual)){
	        		$posicion=["latitud"=>$posActual->getAttribute('latitud'),"longitud"=>$posActual->getAttribute('longitud'),
	        					"fecha"=>$posActual->getAttribute('fecha'),"km"=>$posActual->getAttribute('km_recorridos')];
	        	}
	        	$to 	= date( "Y-m-d H:i", strtotime($viaje->fecha_aviso));
	        	$from 	= date( "Y-m-d H:i", strtotime( $viaje->fecha_aviso." -15 min" ) );
	        	$InicioViaje	= Posiciones::where('movil_id',$movilID->movil_id)
	        								->whereBetween('fecha', array($from, $to))
	        								->first();
	        	if(is_null($InicioViaje)){
	        		$InicioViaje	= Posiciones::where('movil_id',$movilID->movil_id)
	        							->where('fecha','<=',$to)
	        							->orderBy('fecha','desc')
	        							->first();
	        	}
	        	$posInicioViaje=["latitud"=>$InicioViaje->getAttribute('latitud'),"longitud"=>$InicioViaje->getAttribute('longitud'),
	        					"fecha"=>$InicioViaje->getAttribute('fecha'),"km"=>$InicioViaje->getAttribute('km_recorridos')];

	        	$km_recorridos=round(($posicion["km"]-$posInicioViaje["km"]),2);
	        	/*->where('fecha','>=',$from)->where('fecha','<=',$to)echo 'select TOP 1 * from POSICIONES_HISTORICAS where movil_id =', $movilID->movil_id. ' AND fecha BETWEEN ', $from,' AND ',  $to,' order by fecha desc';*/
	        			
	        }
	        config()->set('database.default', 'moviles');
	        $response[]= array("nro_viaje" =>$viaje->nro_plan_carga, 
	        				"posicion"=>["latitud"=>$posicion["latitud"],"longitud"=>$posicion["longitud"]],
	        				"km"=>$km_recorridos,"estado"=>$estadoViajes[$viaje->estado]);
	    }
			/*movil_id"=>$viaje->movil->movil_id "inicioViaje"=>$posInicioViaje,"movil_id"=>$movilID['movil_id'],*/
    		/*$response = ["viajes" =>$viaje->nro_plan_carga, "posicion"=>$posicion,
    					"km"=>$km_recorridos,"estado"=>$estadoViajes[$viaje->estado]];*/
    	return $response;
    }
    public function cerrarViajeMovil($movilViajeAbierto){
    	Log::warning("Movil".$movilViajeAbierto->alias." tiene viaje abierto, se cerrara con fecha:".date('Y-m-d H:i:s'));
    	$viajeCerrar	= $movilViajeAbierto->viajes()->whereNull('fecha_fin')->first();
		$vExist			= Viaje::where('viaje_id', $viajeCerrar->viaje_id)->first();
		$vExist->fecha_fin = date('Y-m-d H:i:s');
	    return $vExist->save();
    }
    public function validaProducto($codigoProducto,$codigoCliente){
    	if(is_null(Producto::where("cod_producto",$codigoProducto)
    						->where("cliente_id",$codigoCliente)->first())){
			Log::warning("se va a crear el producto:". $codigoProducto. "del cliente:".$codigoCliente);
			$prod 				= New Producto;
			$prod->cod_producto	= $codigoProducto;
			$prod->cliente_id	= $codigoCliente;
			$prod->save();
			//return "1";
		}
	}
	public function validaWaypoint($codigoWaypoint,$codigoCliente){
		if(is_null(Waypoint::where("codigo_waypoint",$codigoWaypoint)
							->where("cliente_id",$codigoCliente)
							->first())){
			Log::warning("se va a crear el waypoint:". $codigoWaypoint. "del cliente:".$codigoCliente);
			$way = New Waypoint;
			$way->codigo_waypoint=$codigoWaypoint;
			$way->latitud	= -35;
			$way->longitud	= -57;
			$way->nombre	= $codigoWaypoint;
			$way->nombre_abreviado= $codigoWaypoint;
			$way->cliente_id= $codigoCliente;
			$way->save();

		}
	}
	public function enviarMail($asunto,$cuerpo,$destinatarios){
		$sock = stream_socket_client('tcp://192.168.0.247:2022', $errno, $errstr);
		fwrite($sock, $asunto.";".$cuerpo.";".$destinatarios);
		echo fread($sock, 4096)."\n";
		fclose($sock);
		
	}  
}

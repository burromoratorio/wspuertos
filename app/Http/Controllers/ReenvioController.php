<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use Log;
use App\ReenvioPosicion;
use App\ReenvioPosicionHost;
use App\ReenvioMovil;
use Carbon\Carbon;
use App\Events\ReenvioCreated;
use DB;

class ReenvioController extends Controller
{
    /**
     * Modos de envío soportados
     *
     * @var
     */
    const MODE_CAESSAT='caessat';
    const MODE_PRODTECH='prodtech'; // soap

    /**
     * Estados del envío
     *
     * @var
     */
    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    /**
     * Mapa de ids de rumbos entre SIAC y Prodtech
     *
     * @var
     */
    public $rumbosProdtech = [
        0, // indefinido
        1, // Norte: (siac) 1
        6, // NorthWest: (siac) 2
        4, // West: (siac) 3
        8, // SouthWest: (siac) 4
        2, // South: (siac) 5
        7, // SouthEast: (siac) 6
        3, // East: (siac) 7
        5  // NorthEast: (siac) 8
    ];

    /**
     * Lista los reenvíos
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        return ReenvioPosicion::take(30)->get();
    }

    /**
     * Guarda el reenvío (1 ReenvioPosicion y N ReenvioPosicionHost) en BBDD,
     * y dispara evento para que se comunique a los deamons de envíos, mediante Redis
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function store(Request $request) {
		 //{"movil_id":"11849","hora":"1462346654","patente":"LXG508","latitud":"32.949092","longitud":"60.676610",
        //"velocidad":"0.000000","sentido":"269.120000","posGpsValida":"1","evento":"1","temperatura1":"22","temperatura2":"23","temperatura3":"24"}
        $this->validate($request, [
            'movil_id' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request) {
            ReenvioMovil::
                with('reenvio_host')
                ->where([
                    ['movil_id', $request->input('movil_id')],
                    ['activo', 1],
                ])
                ->each(function ($reenvio_movil) use ($request) {

                   if( $reenvio_movil->reenvio_host->modo == static::MODE_CAESSAT  ){
                       $hostDestino = $reenvio_movil->reenvio_host->destino; 
                       $hostWirtrack= array("arm"=>"190.210.182.161","arm2"=>"190.210.200.196","wirsolut"=>"174.143.201.195","wirsolut2"=>"216.224.163.116","donp"=>"200.55.7.172", "logicTracker"=>"190.104.220.250","sglobal"=>"190.210.189.109","unisolution"=>"190.216.57.166","unisolutionv2"=>"200.69.211.177","linkcargas"=>"168.194.207.130");
                       $hostDhl		= "200.89.128.108";
                       //$cadena		= ($hostDestino==$hostDhl)?$this->mkDhlString($request->all()):$this->mkCaessatString($request->all());
                       $cadena		= ($hostDestino==$hostDhl)?$this->mkDhlString($request->all()):$this->mkCaessatString($request->all(),$hostDestino);
                       foreach($hostWirtrack as $k => $v) {
                          if($hostDestino==$v){
                            $cadena=$this->mkCaessatString17($request->all());
                          }
                           
                       }
                   }else{
                        $cadena=$this->mkSoapString($request->all()); 
                   } 
                    $reenvioPosicion = ReenvioPosicion::create([
                        'movil_id' => $reenvio_movil->movil_id,
                        'cadena' => $cadena,
                    ]);
                    $reenvioPosicionHost = ReenvioPosicionHost::create([
                        'reenvio_posicion_id' => $reenvioPosicion->id,
                        'reenvio_host_id' => $reenvio_movil->reenvio_host_id,
                        'estado_envio_id' => static::ESTADO_PENDIENTE,
                    ]);
                    $reenvioHost = $reenvioPosicionHost->reenvio_host;
					event(new ReenvioCreated(
                        $reenvioPosicionHost->id,
                        $reenvioHost->destino,
                        $reenvioHost->puerto,
                        $reenvioPosicion->cadena,
                        $reenvioHost->protocolo,
                        $reenvio_movil->reenvio_host->modo
                    ));
                });
        }, 3);

        return response()->json("OK\n", 201);
    }

    /**
     * Chequea que el valor $field de $name sea exactamente $length bytes de largo
     *
     * @param  string  $name
     * @param  string  $field
     * @param  int  $length
     * @return string
     * @throws Exception
     */
    private function checkExactLength($name, $field, $length) {
        if (strlen($field) != $length)
            throw new \Exception("Longitud incorrecta del campo: ".$name." - ".$field);
        return $field;
    }
    private function patenteAval($patente){
		$maxFor		= 7-strlen($patente);	
		$espacios		= "";
		for($i=0;$i<$maxFor;$i++){
			$espacios.=" ";
		}
		return $patente.$espacios;
	}
    /**
     * Chequea que el valor $field para agregar el modificador de posicion valida o no
     *
     * @param  string  $field
     * @return string
     */
	private function dhlPositionMod($field) {
        $modificador	= ($field == '1')?"A":"V";
        return $modificador;
    }
	private function dhlEvents($field){
		if( !is_null($field)){
			Try{
				switch ($field){
					Case 1:
						$evento = "03";//posicion
						break;
					Case 2:
						$evento = "07";//panico
						break;
					Case 3:
						$evento = "09";//puerta cabina
						break;
					Case 5:
						$evento = "05";//compuerta
						break;
					Case 6:
						$evento = "48";//antisabotaje
						break;
					Case 8:
						$evento = "15";//encendido
						break;
					Case 20:
						$evento = "08";//enganche, desenganche
						break;
					default:
						//el resto de las alarmas se muestran con 00
						$evento = "00";
				}
			}catch(Exception $e) {
				Log::info('Message: ' .$e->getMessage());
			}
		}
		return $evento;
	}
    /**
     * Arma cadena de envío según protocolo CAESSAT
     *
     * @param  array  $fields
     * @return string
     */
    
    private function mkCaessatString(array $fields,$hostDestino) {
        //PC251210104844HRA450-34.70557-058.49464018360101+00+00+00
        $patente	= ($hostDestino=='200.80.203.67')?$this->patenteAval($fields['patente']):$this->checkExactLength("patente", substr($fields['patente'], 0, 6), 6);
        $cadena =
            "PC".
            $this->checkExactLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).
            $patente.
            $this->checkExactLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).
            $this->checkExactLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).
            $this->checkExactLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).
            $this->checkExactLength("sentido", sprintf("%03d", $fields['sentido']), 3).
            $this->checkExactLength("posGpsValida", $fields['posGpsValida'], 1).
            $this->checkExactLength("evento", sprintf("%02d", $fields['evento']), 2);
        $cadena.=(isset($fields['temperatura1']) && $fields['temperatura1']!='0' )?$this->checkExactLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3):"+99";
        $cadena.=(isset($fields['temperatura2']) && $fields['temperatura2']!='0' )?$this->checkExactLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3):"+99";
        $cadena.=(isset($fields['temperatura3']) && $fields['temperatura3']!='0' )?$this->checkExactLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3):"+99";
        $cadena.="|";
        Log::info($cadena);
        return $cadena;
    }

    private function mkCaessatString17(array $fields) {
        //ABC123,010114210000,-12.34567,+012.34567,80,180,005,100850000,-2,4,-1
        $cadena =
            $fields['patente'].",".
            $this->checkExactLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).",".
            $this->checkExactLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).",".
            $this->checkExactLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).",".
            $this->checkExactLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).",".
            $this->checkExactLength("sentido", sprintf("%03d", $fields['sentido']), 3).",".
            $this->checkExactLength("evento", sprintf("%02d", $fields['evento']), 2).",".
            "0";
	$cadena.=(isset($fields['temperatura1']) && $fields['temperatura1']!='0' )?",".$this->checkExactLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3):",+99";
	$cadena.=(isset($fields['temperatura2']) && $fields['temperatura2']!='0' )?",".$this->checkExactLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3):",+99";
	$cadena.=(isset($fields['temperatura3']) && $fields['temperatura3']!='0' )?",".$this->checkExactLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3):",+99";
        $cadena.="|";
        return $cadena;
    }
    private function mkDhlString(array $fields) {
        //ABC123,010114210000,-12.34567,+012.34567,80,180,005,100850000,-2,4,-1
        $cadena =
            $fields['patente'].
            $this->checkExactLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).
            $this->checkExactLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).
            $this->checkExactLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).
            $this->checkExactLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).
            $this->checkExactLength("sentido", sprintf("%03d", $fields['sentido']), 3).
            $this->dhlEvents($fields['evento']).
            $this->dhlPositionMod($fields['posGpsValida']);
		
        return $cadena;
		
	}
    /**
     * Chequea que el valor $field de $name no supere los $length bytes
     *
     * @param  string  $name
     * @param  string  $field
     * @param  int  $length
     * @return string
     * @throws Exception
     */
    private function checkMaxLength($name, $field, $length) {
        if (strlen($field) > $length)
            throw new \Exception("Longitud incorrecta del campo: ".$name." - ".$field);
        return $field;
    }

    /**
     * Arma JSON de envío según especificación WSDL de Prodtech
     *
     * @param  array  $fields
     * @return string
     */
    private function mkSoapString(array $fields) {
        return json_encode([
            "user" => config('app.prodtech_user'),
            "password" => config('app.prodtech_password'),
            "patente" => $this->checkMaxLength("patente", $fields['patente'], 7),
            "gps_datetime" => Carbon::createFromTimestamp($fields['hora'])->format('Y-m-d H:i:s'),
            "latitud" => $this->checkMaxLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9),
            "longitud" => $this->checkMaxLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10),
            "velocidad" => $this->checkMaxLength("velocidad", sprintf("%03d", $fields['velocidad']), 3),
            "temp_congelado" => "0,0",
            "temp_refrigerado" => "0,0",
            "antena" => $fields['antena'],
            "location_name" => "",
            "direccion" => $this->rumbosProdtech[$fields['sentido_id']],
            "analog3" => "",
            "analog4" => "",
            "analog5" => "",
            "analog6" => "",
        ]);
    }

    /**
     * Actualiza estado de reenvio, y si es necesario, dispara evento para volver a reintentar el envío
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return string
     */
    public function update(Request $request, $id) {
        $this->validate($request, [
            'estado_envio_id' => 'required|numeric',
        ]);

        $reenvioPosicionHost = ReenvioPosicionHost::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        if ($estado == static::ESTADO_PENDIENTE) {
            $mode = $request->input('mode', static::MODE_CAESSAT);
            $reenvioHost = $reenvioPosicionHost->reenvio_host;
            event(new ReenvioCreated(
                $reenvioPosicionHost->id,
                $reenvioHost->destino,
                $reenvioHost->puerto,
                $reenvioPosicionHost->reenvio_posicion->cadena,
                $reenvioHost->protocolo,
                $mode
            ));
        }
        $reenvioPosicionHost->estado_envio_id = $estado;
        $reenvioPosicionHost->save();
        return "Update OK";
    }
}

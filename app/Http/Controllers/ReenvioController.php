<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use Log;
use App\ReenvioPosicion;
use App\ReenvioPosicionHost;
<<<<<<< HEAD
use App\ReenvioMovil;
use Carbon\Carbon;
use App\Events\ReenvioCreated;
use DB;

=======
use DB;
use Carbon\Carbon;
Use Log;
use Illuminate\Support\Facades\Redis;
>>>>>>> 6967b01c0de95b5e018c99613cd9c9b262e19497
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
<<<<<<< HEAD

    /**
     * Guarda el reenvío (1 ReenvioPosicion y N ReenvioPosicionHost) en BBDD,
     * y dispara evento para que se comunique a los deamons de envíos, mediante Redis
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function store(Request $request) {
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
                       $hostDestino=$reenvio_movil->reenvio_host->destino; 
                       if($hostDestino=="200.55.7.172" || $hostDestino=="216.224.163.116"){
           		 $cadena=$this->mkCaessatString17($request->all());
          	       }else{
            		$cadena=$this->mkCaessatString($request->all());
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
=======
/*test con desicion de formacion de cadena*/
public function store(Request $request) {
        //{"movil_id":"11849","hora":"1462346654","patente":"LXG508","latitud":"32.949092","longitud":"60.676610","velocidad":"0.000000","sentido":"269.120000","posGpsValida":"1","evento":"1","temperatura1":"22","temperatura2":"23","temperatura3":"24"}

        $hostMovil = DB::table('reenvios_moviles')->where('movil_id',$request->input('movil_id'))
                                                  ->where('activo','1')
                                                  ->join('reenvios_hosts','reenvios_moviles.reenvio_host_id','reenvios_hosts.id')->get();
        foreach($hostMovil as $host){
          Log::error($host->destino);

          if($host->destino=="200.55.7.172" || $host->destino=="216.224.163.116"){
            $cadena=$this->mkCaessatString17($request->all());
          }else{
            $cadena=$this->mkCaessatString($request->all());
          }

          $reenvioPosicion = ReenvioPosicion::create([
            'movil_id' => $request->input('movil_id'),
            'cadena'=>$cadena
          ]);

          $reenvioPosicionHost = ReenvioPosicionHost::create([
                    'reenvio_posicion_id' => $reenvioPosicion->id,
                    'reenvio_host_id' => $host->reenvio_host_id,
                    'estado_envio_id' => static::ESTADO_PENDIENTE,
          ]);
          $this->publishToRedis(
                    $reenvioPosicionHost->id,
                    $host->destino,
                    $host->puerto,
                    $reenvioPosicion->cadena,
                    $host->protocolo
                );
 

        }
        return "OK\n";
}
>>>>>>> 6967b01c0de95b5e018c99613cd9c9b262e19497

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
<<<<<<< HEAD

    /**
     * Arma cadena de envío según protocolo CAESSAT
     *
     * @param  array  $fields
     * @return string
     */
=======
    
>>>>>>> 6967b01c0de95b5e018c99613cd9c9b262e19497
    private function mkCaessatString(array $fields) {
        //PC251210104844HRA450-34.70557-058.49464018360101+00+00+00
        $cadena =
            "PC".
            $this->checkExactLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).
            $this->checkExactLength("patente", substr($fields['patente'], 0, 6), 6).
            $this->checkExactLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).
            $this->checkExactLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).
            $this->checkExactLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).
            $this->checkExactLength("sentido", sprintf("%03d", $fields['sentido']), 3).
            $this->checkExactLength("posGpsValida", $fields['posGpsValida'], 1).
            $this->checkExactLength("evento", sprintf("%02d", $fields['evento']), 2).
            $this->checkExactLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3).
            $this->checkExactLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3).
            $this->checkExactLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3).
            "|";
<<<<<<< HEAD
Log::error($cadena);
=======
 	Log::error($cadena);           
>>>>>>> 6967b01c0de95b5e018c99613cd9c9b262e19497
        return $cadena;
    }
    private function mkCaessatString17(array $fields) {
        //ABC123,010114210000,-12.34567,+012.34567,80,180,005,100850000,-2,4,-1
        $cadena =
            $fields['patente'].",".
<<<<<<< HEAD
            $this->checkExactLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).",".
            $this->checkExactLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).",".
            $this->checkExactLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).",".
            $this->checkExactLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).",".
            $this->checkExactLength("sentido", sprintf("%03d", $fields['sentido']), 3).",".
            $this->checkExactLength("evento", sprintf("%02d", $fields['evento']), 2).",".
            "0,".
            $this->checkExactLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3).",".
            $this->checkExactLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3).",".
            $this->checkExactLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3)."|";
=======
            $this->checkLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).",".
            $this->checkLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).",".
            $this->checkLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).",".
            $this->checkLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).",".
            $this->checkLength("sentido", sprintf("%03d", $fields['sentido']), 3).",".
            $this->checkLength("evento", sprintf("%02d", $fields['evento']), 2).",".
            "0,".
            $this->checkLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3).",".
            $this->checkLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3).",".
            $this->checkLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3)."|";
>>>>>>> 6967b01c0de95b5e018c99613cd9c9b262e19497
 	Log::error($cadena);           
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
<<<<<<< HEAD
=======

    protected function publishToRedis($id, $host, $port, $msg,$proto) {
        if($proto=='TCP'){
            Log::error("publicando"); 
	    Redis::publish('caessat', json_encode(compact('id', 'host', 'port', 'msg','proto')));
	}else{
	    Redis::publish('caessat-udp',json_encode(compact('id','host','port','msg','proto')) );
	}
    }
>>>>>>> 6967b01c0de95b5e018c99613cd9c9b262e19497
}

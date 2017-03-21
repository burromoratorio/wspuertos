<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ReenvioPosicion;
use App\ReenvioPosicionHost;
use DB;
use Carbon\Carbon;
Use Log;
use Illuminate\Support\Facades\Redis;
class ReenvioController extends Controller
{
    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    public function index(Request $request) {
        return ReenvioPosicion::take(30)->get();
    }
/*test con desicion de formacion de cadena*/
public function store(Request $request) {
        //{"movil_id":"11849","hora":"1462346654","patente":"LXG508","latitud":"32.949092","longitud":"60.676610","velocidad":"0.000000","sentido":"269.120000","posGpsValida":"1","evento":"1","temperatura1":"22","temperatura2":"23","temperatura3":"24"}

        $hostMovil = DB::table('reenvios_moviles')->where('movil_id',$request->input('movil_id'))
                                                  ->where('activo','1')
                                                  ->join('reenvios_hosts','reenvios_moviles.reenvio_host_id','reenvios_hosts.id')->get();
        foreach($hostMovil as $host){
          Log::error($host->destino);

          if($host->destino=="200.55.7.172"){
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

    private function checkLength($name, $field, $length) {
        if (strlen($field) != $length)
            throw new \Exception("Longitud incorrecta del campo: ".$name." - ".$field);
        return $field;
    }
    
    private function mkCaessatString(array $fields) {
        //PC251210104844HRA450-34.70557-058.49464018360101+00+00+00
        $cadena =
            "PC".
            $this->checkLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).
            $this->checkLength("patente", substr($fields['patente'], 0, 6), 6).
            $this->checkLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).
            $this->checkLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).
            $this->checkLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).
            $this->checkLength("sentido", sprintf("%03d", $fields['sentido']), 3).
            $this->checkLength("posGpsValida", $fields['posGpsValida'], 1).
            $this->checkLength("evento", sprintf("%02d", $fields['evento']), 2).
            $this->checkLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3).
            $this->checkLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3).
            $this->checkLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3).
            "|";
 	Log::error($cadena);           
        return $cadena;
    }
    private function mkCaessatString17(array $fields) {
        //ABC123,010114210000,-12.34567,+012.34567,80,180,005,100850000,-2,4,-1
        $cadena =
            $fields['patente'].",".
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
 	Log::error($cadena);           
        return $cadena;
    }

    public function update(Request $request, $id) {
        $reenvioPosicionHost = ReenvioPosicionHost::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        if ($estado == static::ESTADO_PENDIENTE) {
            $reenvioHost = $reenvioPosicionHost->reenvio_host;
            $this->publishToRedis(
                $reenvioPosicionHost->id,
                $reenvioHost->destino,
                $reenvioHost->puerto,
                $reenvioPosicionHost->reenvio_posicion->cadena,
                $reenvioHost->protocolo
            );
        }
        $reenvioPosicionHost->estado_envio_id = $estado;
        $reenvioPosicionHost->save();
        return "Update OK";
    }

    protected function publishToRedis($id, $host, $port, $msg,$proto) {
        if($proto=='TCP'){
            Log::error("publicando"); 
	    Redis::publish('caessat', json_encode(compact('id', 'host', 'port', 'msg','proto')));
	}else{
	    Redis::publish('caessat-udp',json_encode(compact('id','host','port','msg','proto')) );
	}
    }
}

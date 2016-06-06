<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\ReenvioPosicion;
use App\Movil;
use Log;
use Carbon\Carbon;
use Redis;

class ReenvioController extends Controller
{
    public function index(Request $request) {
        $reenvios = ReenvioPosicion::with('estado_envio')->orderBy('id', 'desc')->take(30)->get();
        return view('reenvios.index')->with([
            'title' => 'Reenvios',
            'registers' => $reenvios,
        ]);
    }

    public function store(Request $request) {
        //{"movil_id":"11849","hora":"1462346654","patente":"LXG508","latitud":"32.949092","longitud":"60.676610","velocidad":"0.000000","sentido":"269.120000","posGpsValida":"1","evento":"1","temperatura1":"22","temperatura2":"23","temperatura3":"24"}
        $reenvioHost = Movil::findOrFail($request->input('movil_id'))->reenvio_movil->reenvio_host;
        $caessatString = $this->mkCaessatString($request->all());
        $reenvioPosicion = ReenvioPosicion::create([
            'movil_id' => $request->input('movil_id'),
            'reenvio_host_id' => $reenvioHost->id,
            'estado_envio_id' => 1,
            'cadena' => $caessatString,
        ]);
        $this->publishToRedis($reenvioPosicion->id, $reenvioHost->destino, $reenvioHost->puerto, $caessatString);
        return "OK\n";
    }

    private function mkCaessatString(array $fields) {
        //PC251210104844HRA450-34.70557-058.49464018360101+00+00+00
        $cadena = 
            "PC".
            Carbon::createFromTimestamp($fields['hora'])->format('dmyHis').
            substr($fields['patente'], 0, 6).
            sprintf("%+02.5f", $fields['latitud']).
            sprintf("%+03.5f", $fields['longitud']).
            sprintf("%03d", $fields['velocidad']).
            sprintf("%03d", $fields['sentido']).
            $fields['posGpsValida'].
            sprintf("%02d", $fields['evento']).
            sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']).
            sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']).
            sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']).
            "|";
        return $cadena;
    }

    public function update(Request $request, $id) {
        $reenvioPosicion = ReenvioPosicion::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        Log::debug("Estado recibido: ".$estado);
        if ($estado == 1) {
            $reenvioHost = $reenvioPosicion->reenvio_host;
            $this->publishToRedis($reenvioPosicion->id, $reenvioHost->destino, $reenvioHost->puerto, $reenvioPosicion->cadena);
        }
        $reenvioPosicion->estado_envio_id = $estado;
        $reenvioPosicion->save();
        return "Update OK";
    }

    protected function publishToRedis($id, $host, $port, $msg) {
        Redis::publish('caessat', json_encode(compact('id', 'host', 'port', 'msg')));
    }
}

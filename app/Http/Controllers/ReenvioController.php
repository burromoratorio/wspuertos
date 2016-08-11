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
    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    public function index(Request $request) {
        if (!request()->wantsJson()) {
            return view('reenvios.index')->with([
                'title' => 'Reenvios',
            ]);
        }
        return ReenvioPosicion::with('estado_envio')
            ->orderBy('id', 'desc')
            ->take(30);
    }

    public function store(Request $request) {
        //{"movil_id":"11849","hora":"1462346654","patente":"LXG508","latitud":"32.949092","longitud":"60.676610","velocidad":"0.000000","sentido":"269.120000","posGpsValida":"1","evento":"1","temperatura1":"22","temperatura2":"23","temperatura3":"24"}
        $reenvioPosicion = ReenvioPosicion::create([
            'movil_id' => $request->input('movil_id'),
            'cadena' => $this->mkCaessatString($request->all()),
        ]);
        $reenvioPosicion
            ->reenvios_moviles
            ->each(function ($reenvio_movil) use ($reenvioPosicion) {
                $reenvioPosicionHost = ReenvioPosicionHost::create([
                    'reenvio_posicion_id' => $reenvioPosicion->id,
                    'reenvio_host_id' => $reenvio_movil->reenvio_host_id,
                    'estado_envio_id' => static::ESTADO_PENDIENTE,
                ]);
                $reenvioHost = $reenvioPosicionHost->reenvio_host;
                $this->publishToRedis($reenvioPosicionHost->id, $reenvioHost->destino,
                    $reenvioHost->puerto, $reenvioPosicion->cadena);

            });
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
        $reenvioPosicionHost = ReenvioPosicionHost::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        Log::debug("Estado recibido: ".$estado);
        if ($estado == static::ESTADO_PENDIENTE) {
            $reenvioHost = $reenvioPosicionHost->reenvio_host;
            $this->publishToRedis($reenvioPosicionHost->id, $reenvioHost->destino,
                $reenvioHost->puerto, $reenvioPosicion->cadena);
        }
        $reenvioPosicionHost->estado_envio_id = $estado;
        $reenvioPosicionHost->save();
        return "Update OK";
    }

    protected function publishToRedis($id, $host, $port, $msg) {
        Redis::publish('caessat', json_encode(compact('id', 'host', 'port', 'msg')));
    }
}

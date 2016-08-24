<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\ReenvioPosicion;
use App\ReenvioPosicionHost;
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
            ->filter(function($value) { return $value->activo == '1' ;})
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
        return $cadena;
    }

    public function update(Request $request, $id) {
        $reenvioPosicionHost = ReenvioPosicionHost::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        Log::debug("Estado recibido: ".$estado);
        if ($estado == static::ESTADO_PENDIENTE) {
            $reenvioHost = $reenvioPosicionHost->reenvio_host;
            $this->publishToRedis($reenvioPosicionHost->id, $reenvioHost->destino,
                $reenvioHost->puerto, $reenvioPosicionHost->reenvio_posicion->cadena);
        }
        $reenvioPosicionHost->estado_envio_id = $estado;
        $reenvioPosicionHost->save();
        return "Update OK";
    }

    protected function publishToRedis($id, $host, $port, $msg) {
        Redis::publish('caessat', json_encode(compact('id', 'host', 'port', 'msg')));
    }
}

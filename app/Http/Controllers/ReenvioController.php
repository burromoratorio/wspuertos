<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\ReenvioPosicion;
use Log;
use Carbon\Carbon;
use Redis;

class ReenvioController extends Controller
{
    public function index(Request $request) {
        $reenvios = ReenvioPosicion::with('estado_envio')->get();
        return view('reenvios.index')->with([
            'title' => 'Reenvios',
            'registers' => $reenvios,
        ]);
    }

    public function store(Request $request) {
        //Log::debug("json: ".json_encode($request->all()));
        //{"movil_id":"11849","hora":"1462346654","patente":"LXG508","latitud":"32.949092","longitud":"60.676610","velocidad":"0.000000","sentido":"269.120000","posGpsValida":"1","evento":"1","temperatura1":"22","temperatura2":"23","temperatura3":"24"}
        $caessatString = $this->mkCaessatString($request->all());
        $reenvioPosicion = ReenvioPosicion::create([
            'movil_id' => $request->input('movil_id'),
            'reenvio_host_id' => 1,
            'estado_envio_id' => 1,
            'cadena' => $caessatString,
        ]);
        $key = $request->input('movil_id').":".$request->input('hora');
        $this->publish($reenvioPosicion->id, $caessatString);
        return "OK\n";
    }

    private function mkCaessatString(array $fields) {
        //PC251210104844HRA450-34.70557-058.49464018360101+00+00+00
        $cadena = 
            "PC".
            Carbon::createFromTimestamp($fields['hora'])->format('dmyHis').
            $fields['patente']. // TRUNCAR ESTO
            sprintf("%+02.5f", $fields['latitud']).
            sprintf("%+03.5f", $fields['longitud']).
            sprintf("%03d", $fields['velocidad']).
            sprintf("%03d", $fields['sentido']).
            $fields['posGpsValida'].
            sprintf("%02d", $fields['evento']).
            sprintf("%+03d", $fields['temperatura1']).
            sprintf("%+03d", $fields['temperatura2']).
            sprintf("%+03d", $fields['temperatura3']).
            "|"
            ;
        return $cadena;
    }

    public function update(Request $request, $id) {
        $reenvio = ReenvioPosicion::findOrFail($id);
        $estado = $request->input('estado');
        Log::debug("Estado recibido: ".$estado);
        if ($estado == 1) {
            $this->publish($reenvio->id, $reenvio->cadena);
        }
        $reenvio->estado_envio_id = $estado;
        $reenvio->save();
        return "Update OK\n";
    }

    protected function publish($id, $report) {
        Redis::publish('caessat', json_encode([
            'id' => $id,
            'msg' => $report,
        ]));
    }
}

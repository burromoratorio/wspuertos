<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Evento;
use App\AvisoCliente;
use App\AvisoMovil;
use Illuminate\Support\Facades\Redis;

class EventoController extends Controller
{
    public function index(Request $request) {
        return Evento::take(30)->get();
    }

    public function store(Request $request) {
        $this->validate($request, [
            'evento_tipo_id' => 'required|numeric',
            'movil_id' => 'required|numeric',
            'waypoint_id' => 'required|numeric',
            'cliente_id' => 'required|numeric',
        ]);
        $evento = Evento::create([
            'evento_tipo_id' => $request->input('evento_tipo_id'),
            'movil_id' => $request->input('movil_id'),
            'eventable_id' => $request->input('waypoint_id'),
            'eventable_type' => 'App\Waypoint',
        ]);
        $this->checkMail($request->only('cliente_id', 'movil_id'));
        return "OK";
    }

    protected function checkMail($input) {
        if (AvisoCliente::find($input['cliente_id'])) {
            if (AvisoMovil::find($input['movil_id']) || !AvisoMovil::count()) {
                $this->sendMail($movil, $cliente, $evento);
            }
        }
    }

    protected function sendMail($movil, $cliente, $evento) {
        // TODO: terminar de implementar
        Redis::publish('mails', json_encode(compact('id', 'msg')));
    }
}

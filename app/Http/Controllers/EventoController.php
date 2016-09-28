<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Evento;
use App\Movil;
use App\Aviso;
use App\Waypoint;
use App\AvisoCliente;
use App\AvisoMovil;
use App\AvisoDestinatario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class EventoController extends Controller
{
    const AVISO_ENTRADA_WAYPOINT = 1;
    const AVISO_SALIDA_WAYPOINT = 2;

    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    protected $aviso_cliente;

    public function index(Request $request) {
        return Evento::take(30)->get();
    }

    public function store(Request $request) {
        $this->validate($request, [
            'evento_tipo_id' => 'required|numeric',
            'cliente_id' => 'required|numeric',
            'movil_id' => 'required|numeric',
            'dominio' => 'required|alpha_num',
            'timestamp' => 'required|numeric',
            'waypoint_id' => 'required|numeric',
        ]);
        $evento_tipo_id = $request->input('evento_tipo_id');
        $movil_id = $request->input('movil_id');
        $dominio = $request->input('dominio');
        $waypoint_id = $request->input('waypoint_id');
        $evento = Evento::create([
            'evento_tipo_id' => $evento_tipo_id,
            'movil_id' => $movil_id,
            'eventable_id' => $waypoint_id,
            'eventable_type' => 'App\Waypoint',
        ]);
        $send = $this->checkNotificacion($request->only('cliente_id', 'movil_id'));
        if ($send) {
            $addresses = AvisoDestinatario
                ::where('aviso_cliente_id', $this->aviso_cliente->id)
                ->with('destinatario')
                ->map(function($aviso_destinatario) {
                    return $aviso_destinatario->destinatario;
                });
            $waypoint = Waypoint::find($waypoint_id);
            $info_mail = $this->makeMailWaypoint($dominio, $evento_tipo_id, $timestamp, $waypoint);
            $this->sendMail($info_mail['subject'], $info_mail['body'], implode(",", $addresses));
        }
        return "OK";
    }

    protected function checkNotificacion($input) {
        $this->aviso_cliente = AvisoCliente::find($input['cliente_id']);
        return $this->aviso_cliente && (
            !AvisoMovil::count() || AvisoMovil::find($input['movil_id'])
        );
    }

    protected function makeMailWaypoint($dominio, $evento_tipo_id, $timestamp, $waypoint) {
        if ($evento_tipo_id == static::AVISO_ENTRADA_WAYPOINT) {
            $subject = "SIAC - ".$dominio." ingresa al waypoint: " + $waypoint->nombre;
            $body = "Hora de egreso: ".Carbon::createFromTimestamp($timestamp)->format('%d/%m/%y %X"');
        } else if ($evento_tipo_id == static::AVISO_SALIDA_WAYPOINT) {
            $subject = "SIAC - $dominio sale del waypoint: ".$waypoint->nombre;
            $body = "Hora de ingreso: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('%d/%m/%y %X"');
        }
        return compact('subject', 'body');
    }

    protected function sendMail($subject, $body, $addresses) {
        $id = Aviso::create([
            'aviso_cliente_id' => $this->aviso_cliente->id,
            'estado_envio_id' => static::ESTADO_PENDIENTE,
            'aviso' => "$subject;$body;$addresses",
        ])->id;
        Redis::publish('mails', json_encode(compact('id', 'subject', 'body', 'addresses')));
    }
}

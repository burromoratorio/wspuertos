<?php

namespace App\Http\Controllers;

use App\Aviso;
use App\AvisoCliente;
use App\AvisoMovil;
use App\AvisoConfiguracion;
use App\AvisoDestinatario;
use App\Waypoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

trait AvisoTrait
{
    protected $aviso_cliente;

    protected function mustNotify($movil_id, $cliente_id, $aviso_tipo_id, $waypoint_id) {
        $this->aviso_cliente = AvisoCliente::where([
            ['cliente_id', $cliente_id],
            ['aviso_tipo_id', $aviso_tipo_id],
        ])->first();
        return $this->aviso_cliente && (
            !AvisoMovil::where('aviso_cliente_id', $this->aviso_cliente->id)->first() ||
            AvisoMovil::where([
                ['aviso_cliente_id', $this->aviso_cliente->id],
                ['movil_id', $movil_id],
            ])->first()
        ) && (
            !AvisoConfiguracion::where('aviso_cliente_id', $this->aviso_cliente->id)->first() ||
            AvisoConfiguracion::where([
                ['aviso_cliente_id', $this->aviso_cliente->id],
                ['valor', $waypoint_id],
            ])->first()
        );
    }

    protected function createAviso($aviso) {
        return Aviso::create([
            'aviso_cliente_id' => $this->aviso_cliente->id,
            'estado_envio_id' => static::ESTADO_PENDIENTE,
            'aviso' => $aviso,
        ])->id;
    }

    protected function notify($subject, $body) {
        $subject = str_replace(";", " ", $subject); // evita que se rompa la cadena a enviar
        $aviso_id = $this->createAviso("$subject;$body");
        $addresses = AvisoDestinatario
            ::where('aviso_cliente_id', $this->aviso_cliente->id)
            ->with('destinatario')
            ->get()
            ->map(function($aviso_destinatario) {
                return $aviso_destinatario->destinatario->mail;
            })
            ->toArray();
        $this->sendAviso($aviso_id, $subject, $body, implode(",", $addresses));
    }

    protected function makeMailWaypoint($dominio, $evento_tipo_id, $timestamp, $waypoint_id) {
        $waypoint = Waypoint::find($waypoint_id);
        if ($evento_tipo_id == static::AVISO_ENTRADA_WAYPOINT) {
            $subject = "SIAC - ".$dominio." ingresa al waypoint: ".$waypoint->nombre;
            $body = "Hora de ingreso: ".Carbon::createFromTimestamp($timestamp)->format('d/m/Y H:i:s');
        } else if ($evento_tipo_id == static::AVISO_SALIDA_WAYPOINT) {
            $subject = "SIAC - $dominio sale del waypoint: ".$waypoint->nombre;
            $body = "Hora de salida: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        } else {
            throw new \Exception("evento_tipo_id desconocido");
        }
        return compact('subject', 'body');
    }

    protected function sendAviso($aviso_id, $subject, $body, $addresses) {
        if ($addresses == "") throw new \Exception("Falta mail para el aviso: $aviso_id. $subject");
        //Redis::publish('mails', json_encode(compact('aviso_id', 'subject', 'body', 'addresses')));
    }
}

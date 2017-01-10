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
    // avisos
    static $AVISO_ENTRADA_WAYPOINT = 1;
    static $AVISO_SALIDA_WAYPOINT = 2;
    static $AVISO_DESENGANCHE = 3;

    // estado envios
    static $ESTADO_PENDIENTE = 1;
    static $ESTADO_ENVIADO = 2;
    static $ESTADO_FALLIDO = 3;

    protected function getAvisosCliente($movil_id, $cliente_id, $aviso_tipo_id, $entity_id) {
        return AvisoCliente
            ::where([
                ['cliente_id', $cliente_id],
                ['aviso_tipo_id', $aviso_tipo_id],
            ])
            ->get()
            ->filter(function($aviso_cliente) use ($movil_id, $entity_id) {
                return (
                    !AvisoMovil::where([
                        ['aviso_cliente_id', $aviso_cliente->id]
                    ])->first() ||
                    AvisoMovil::where([
                        ['aviso_cliente_id', $aviso_cliente->id],
                        ['movil_id', $movil_id],
                    ])->first()
                ) && (
                    !AvisoConfiguracion::where([
                        ['aviso_cliente_id', $aviso_cliente->id]
                    ])->first() ||
                    AvisoConfiguracion::where([
                        ['aviso_cliente_id', $aviso_cliente->id],
                        ['valor', $entity_id],
                    ])->first()
                );
            });
    }

    protected function createAviso($aviso, $aviso_cliente_id) {
        return Aviso::create([
            'aviso_cliente_id' => $aviso_cliente_id,
            'estado_envio_id' => self::$ESTADO_PENDIENTE,
            'aviso' => $aviso,
        ])->id;
    }

    protected function notify($subject, $body, $aviso_cliente_id) {
        $subject = str_replace(";", " ", $subject); // evita que se rompa la cadena a enviar
        $aviso_id = $this->createAviso("$subject;$body", $aviso_cliente_id);
        $addresses = AvisoDestinatario
            ::where('aviso_cliente_id', $aviso_cliente_id)
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
        if ($evento_tipo_id == self::$AVISO_ENTRADA_WAYPOINT) {
            $subject = "SIAC - ".$dominio." ingresa al waypoint: ".$waypoint->nombre;
            $body = "Hora de ingreso: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        } else if ($evento_tipo_id == self::$AVISO_SALIDA_WAYPOINT) {
            $subject = "SIAC - $dominio sale del waypoint: ".$waypoint->nombre;
            $body = "Hora de salida: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        } else {
            throw new \Exception("evento_tipo_id desconocido");
        }
        return compact('subject', 'body');
    }

    protected function makeMailDesenganche($dominio, $evento_tipo_id, $timestamp, $posicion_id) {
        if ($evento_tipo_id == self::$AVISO_DESENGANCHE) {
            $subject = "SIAC - ".$dominio." desenganchó";
            $body = "Hora de desenganche: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        } else {
            throw new \Exception("evento desconocido");
        }
        return compact('subject', 'body');
    }

    protected function sendAviso($aviso_id, $subject, $body, $addresses) {
        if ($addresses == "") throw new \Exception("Falta mail para el aviso: $aviso_id. $subject");
        Redis::publish('mails', json_encode(compact('aviso_id', 'subject', 'body', 'addresses')));
    }
}

<?php

namespace App\Http\Controllers;

use App\Aviso;
use App\AvisoCliente;
use App\AvisoMovil;
use App\AvisoConfiguracion;
use App\AvisoDestinatario;
use App\Waypoint;
use Carbon\Carbon;
use App\Events\AvisoCreated;

trait AvisoTrait
{
    /**
     * Avisos soportados por el sistema
     *
     * @var
     */
    static $AVISO_ENTRADA_WAYPOINT = 1;
    static $AVISO_SALIDA_WAYPOINT = 2;
    static $AVISO_DESENGANCHE = 3;
    static $AVISO_ENGANCHE = 4;

    /**
     * Estados de envío
     *
     * @var
     */
    static $ESTADO_PENDIENTE = 1;
    static $ESTADO_ENVIADO = 2;
    static $ESTADO_FALLIDO = 3;

    /**
     * Obtiene los clientes que tienen configurado el envío para el evento sucedido
     *
     * @param  int  $movil_id
     * @param  int  $cliente_id
     * @param  int  $aviso_tipo_id
     * @param  int  $entity_id
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Crea nuevo registro de aviso para ser enviado y devuelve su id
     *
     * @param  int  $aviso
     * @param  int  $aviso_cliente_id
     * @return int
     */
    protected function createAviso($aviso, $aviso_cliente_id) {
        return Aviso::create([
            'aviso_cliente_id' => $aviso_cliente_id,
            'estado_envio_id' => self::$ESTADO_PENDIENTE,
            'aviso' => $aviso,
        ])->id;
    }

    /**
     * Crea el aviso y dispara evento para notificar al cliente
     *
     * @param  string  $subject
     * @param  string  $body
     * @param  int  $aviso_cliente_id
     * @return void
     */
    protected function notify($subject, $body, $aviso_cliente_id) {
        $subject = str_replace(";", " ", $subject); // evita que se rompa la cadena a enviar
        $aviso_id = $this->createAviso("$subject;$body", $aviso_cliente_id);
        $this->fireEvent($subject, $body, $aviso_cliente_id, $aviso_id);
    }

    /**
     * Dispara evento para notificar al cliente
     *
     * @param  string  $subject
     * @param  string  $body
     * @param  int  $aviso_cliente_id
     * @param  int  $aviso_id
     * @return void
     */
    protected function fireEvent($subject, $body, $aviso_cliente_id, $aviso_id) {
        $addresses = AvisoDestinatario
            ::where('aviso_cliente_id', $aviso_cliente_id)
            ->with('destinatario')
            ->get()
            ->map(function($aviso_destinatario) {
                return $aviso_destinatario->destinatario->mail;
            })
            ->toArray();
        event(new AvisoCreated($aviso_id, $subject, $body, implode(",", $addresses)));
    }

    /**
     * Arma mail para envío para avisos de waypoints
     *
     * @param  string  $dominio
     * @param  int  $evento_tipo_id
     * @param  int  $timestamp
     * @param  int  $waypoint_id
     * @return array
     */
    protected function makeMailWaypoint($dominio, $evento_tipo_id, $timestamp, $waypoint_id) {
        if ($evento_tipo_id != self::$AVISO_ENTRADA_WAYPOINT &&
            $evento_tipo_id != self::$AVISO_SALIDA_WAYPOINT)
        {
            throw new \Exception("evento_tipo_id desconocido");
        }

        $waypoint = Waypoint::find($waypoint_id);

        if ($evento_tipo_id == self::$AVISO_ENTRADA_WAYPOINT)
        {
            $subject = "SIAC - $dominio ingresa al waypoint: ".$waypoint->nombre;
            $body = "Hora de ingreso: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        }
        else if ($evento_tipo_id == self::$AVISO_SALIDA_WAYPOINT)
        {
            $subject = "SIAC - $dominio sale del waypoint: ".$waypoint->nombre;
            $body = "Hora de salida: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        }
        return compact('subject', 'body');
    }

    /**
     * Arma mail para envío para avisos de desenganche
     *
     * @param  string  $dominio
     * @param  int  $evento_tipo_id
     * @param  int  $timestamp
     * @param  int  $posicion_id
     * @return array
     */
    protected function makeMailDesenganche($dominio, $evento_tipo_id, $timestamp, $posicion_id) {
        if ($evento_tipo_id != self::$AVISO_DESENGANCHE &&
            $evento_tipo_id != self::$AVISO_ENGANCHE)
        {
            throw new \Exception("evento desconocido");
        }

        $keyword = $evento_tipo_id == self::$AVISO_DESENGANCHE ? "des" : "";
        $subject = "SIAC - $dominio ${keyword}engancha";
        $body = "Hora de ${keyword}enganche: ".Carbon::createFromTimestamp($timestamp-3*60*60)->format('d/m/Y H:i:s');
        return compact('subject', 'body');
    }
}

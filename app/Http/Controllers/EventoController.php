<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Evento;
use DB;

class EventoController extends Controller
{
    use AvisoTrait;

    /**
     * Eventos soportados
     *
     * @var
     */
    const EVENTO_ENTRADA_WAYPOINT = 1;
    const EVENTO_SALIDA_WAYPOINT = 2;
    const EVENTO_DESENGANCHE = 3;
    const EVENTO_ENGANCHE = 4;

    /**
     * Eventos que se avisarán
     *
     * @var
     */
    const AVISO_ENTRADA_WAYPOINT = 1;
    const AVISO_SALIDA_WAYPOINT = 2;
    const AVISO_DESENGANCHE = 3;
    const AVISO_ENGANCHE = 4;

    /**
     * Posición fake hasta que las posiciones se registren en la nueva BBDD
     *
     * @var
     */
    const FAKE_POSICION_ID = 1;

    /**
     * Mapa de eventos a tipo_aviso
     *
     * @var
     */
    protected $eventos_avisos = [
        self::EVENTO_ENTRADA_WAYPOINT => self::AVISO_ENTRADA_WAYPOINT,
        self::EVENTO_SALIDA_WAYPOINT => self::AVISO_SALIDA_WAYPOINT,
        self::EVENTO_DESENGANCHE => self::AVISO_DESENGANCHE,
        self::EVENTO_ENGANCHE => self::AVISO_ENGANCHE,
    ];

    /**
     * Tipo de evento recibido
     *
     * @var
     */
    protected $evento_tipo_id;

    /**
     * Cliente perteneciente al evento
     *
     * @var
     */
    protected $cliente_id;

    /**
     * Móvil afectado por el evento
     *
     * @var
     */
    protected $movil_id;

    /**
     * Patente del móvil
     *
     * @var
     */
    protected $dominio;

    /**
     * Hora gps del evento
     *
     * @var
     */
    protected $timestamp;

    /**
     * Lista los eventos
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return Evento::take(30)->get();
    }

    /**
     * Guarda el evento en BBDD, y si hay que avisar al cliente, se envía un mail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function store(Request $request) {
        $this->validate($request, [
            'evento_tipo_id' => 'required|numeric',
            'cliente_id' => 'required|numeric',
            'movil_id' => 'required|numeric',
            'dominio' => 'required|alpha_num',
            'timestamp' => 'required|numeric',
            'waypoint_id' => 'required_if:evento_tipo_id,1,2|numeric', // requerido si entrada/salida wp
        ]);

        $this->evento_tipo_id = $request->input('evento_tipo_id');
        $this->cliente_id = $request->input('cliente_id');
        $this->movil_id = $request->input('movil_id');
        $this->dominio = $request->input('dominio');
        $this->timestamp = $request->input('timestamp');

        if ($this->evento_tipo_id == self::EVENTO_ENTRADA_WAYPOINT ||
            $this->evento_tipo_id == self::EVENTO_SALIDA_WAYPOINT) {
            $entity_id = $request->input('waypoint_id');
            $eventable_type = 'App\Waypoint';
            $adviseMethod = 'makeMailWaypoint';
        } else if ($this->evento_tipo_id == self::EVENTO_DESENGANCHE ||
                 $this->evento_tipo_id == self::EVENTO_ENGANCHE) {
            $entity_id = self::FAKE_POSICION_ID;
            $eventable_type = 'App\Posicion';
            $adviseMethod = 'makeMailDesenganche';
        } else {
            throw new \Exception("Evento desconocido");
        }

        DB::transaction(function () use ($entity_id, $eventable_type, $adviseMethod) {
            Evento::create([
                'evento_tipo_id' => $this->evento_tipo_id,
                'movil_id' => $this->movil_id,
                'eventable_id' => $entity_id,
                'eventable_type' => $eventable_type,
            ]);

            if ($this->isAdvisable()) {
                $this->advise($entity_id, $adviseMethod);
            }
        }, 3);

        return response()->json("OK", 201);
    }

    /**
     * Chequea si el evento recibido debe avisarse al cliente
     *
     * @return bool
     */
    protected function isAdvisable() {
        return collect($this->eventos_avisos)->keys()->contains($this->evento_tipo_id);
    }

    /**
     * Avisa al cliente del evento sucedido
     *
     * @param  int  $entity_id
     * @param  string  $adviseMethod
     * @return bool
     */
    protected function advise($entity_id, $adviseMethod) {
        $aviso_tipo_id = $this->eventos_avisos[$this->evento_tipo_id];
        $this->getAvisosCliente($this->movil_id, $this->cliente_id, $aviso_tipo_id, $entity_id)
            ->each(function($aviso_cliente) use ($entity_id, $adviseMethod) {
                $info_mail = $this->{$adviseMethod}(
                    $this->dominio, $this->evento_tipo_id, $this->timestamp, $entity_id
                );
                $this->notify($info_mail['subject'], $info_mail['body'], $aviso_cliente->id);
            });
    }
}

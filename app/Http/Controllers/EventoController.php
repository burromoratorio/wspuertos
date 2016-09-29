<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Evento;

class EventoController extends Controller
{
    use AvisosTrait;

    // eventos
    const EVENTO_ENTRADA_WAYPOINT = 1;
    const EVENTO_SALIDA_WAYPOINT = 2;
    // avisos
    const AVISO_ENTRADA_WAYPOINT = 1;
    const AVISO_SALIDA_WAYPOINT = 2;
    // estados
    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;
    // eventos notificables
    protected $notifiable_events = [
        self::EVENTO_ENTRADA_WAYPOINT,
        self::EVENTO_SALIDA_WAYPOINT,
    ];
    // mapa de evento a tipo_aviso
    protected $eventos_avisos = [
        self::EVENTO_ENTRADA_WAYPOINT => self::AVISO_ENTRADA_WAYPOINT,
        self::EVENTO_SALIDA_WAYPOINT => self::AVISO_SALIDA_WAYPOINT,
    ];

    // propiedades del evento
    protected $evento_tipo_id;
    protected $cliente_id;
    protected $movil_id;
    protected $dominio;
    protected $timestamp;
    protected $waypoint_id;

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

        $this->evento_tipo_id = $request->input('evento_tipo_id');
        $this->cliente_id = $request->input('cliente_id');
        $this->movil_id = $request->input('movil_id');
        $this->dominio = $request->input('dominio');
        $this->timestamp = $request->input('timestamp');
        $this->waypoint_id = $request->input('waypoint_id');

        Evento::create([
            'evento_tipo_id' => $this->evento_tipo_id,
            'movil_id' => $this->movil_id,
            'eventable_id' => $this->waypoint_id,
            'eventable_type' => 'App\Waypoint', // mandarlo desde el puerto
        ]);
        $this->processAviso();
        return "OK";
    }

    protected function isNotifiable($evento_tipo_id) {
        return array_key_exists($evento_tipo_id, $this->notifiable_events);
    }

    protected function processAviso() {
        if (!$this->isNotifiable($this->evento_tipo_id)) return;

        $aviso_tipo_id = $this->eventos_avisos[$this->evento_tipo_id];
        if (!$this->mustNotify($this->movil_id, $this->cliente_id, $aviso_tipo_id)) return;
        \Log::debug("lo notifica");
        // esto va a terminar siendo un condicional
        $info_mail = $this->makeMailWaypoint($this->dominio, $this->evento_tipo_id, $this->timestamp, $this->waypoint_id);
        $this->notify($info_mail['subject'], $info_mail['body']);
    }
}

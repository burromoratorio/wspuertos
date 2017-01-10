<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Evento;

class EventoController extends Controller
{
    use AvisoTrait;

    // eventos
    static $EVENTO_ENTRADA_WAYPOINT = 1;
    static $EVENTO_SALIDA_WAYPOINT = 2;
    static $EVENTO_DESENGANCHE = 3;

    static $FAKE_POSICION_ID = 1; // hasta que se utilice POSICIONES en la bbdd nueva

    // mapa de evento a tipo_aviso
    protected $eventos_avisos = [
        self::$EVENTO_ENTRADA_WAYPOINT => self::$AVISO_ENTRADA_WAYPOINT,
        self::$EVENTO_SALIDA_WAYPOINT => self::$AVISO_SALIDA_WAYPOINT,
        self::$EVENTO_DESENGANCHE => self::$AVISO_DESENGANCHE,
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
            'waypoint_id' => 'required_if:evento_tipo_id,1,2|numeric', // requerido si entrada/salida wp
        ]);

        $this->evento_tipo_id = $request->input('evento_tipo_id');
        $this->cliente_id = $request->input('cliente_id');
        $this->movil_id = $request->input('movil_id');
        $this->dominio = $request->input('dominio');
        $this->timestamp = $request->input('timestamp');

        if ($this->evento_tipo_id == self::$EVENTO_ENTRADA_WAYPOINT ||
            $this->evento_tipo_id == self::$EVENTO_SALIDA_WAYPOINT)
        {
            $this->waypoint_id = $request->input('waypoint_id');
            Evento::create([
                'evento_tipo_id' => $this->evento_tipo_id,
                'movil_id' => $this->movil_id,
                'eventable_id' => $this->waypoint_id,
                'eventable_type' => 'App\Waypoint',
            ]);
        } else if ($this->evento_tipo_id == self::$EVENTO_DESENGANCHE) {
            Evento::create([
                'evento_tipo_id' => $this->evento_tipo_id,
                'movil_id' => $this->movil_id,
                'eventable_id' => 0,
                'eventable_type' => 'App\Posicion',
            ]);
        } else {
            throw new \Exception("Evento desconocido");
        }

        if ($this->isAdvisable($this->evento_tipo_id)) {
            $this->advise();
        }

        return "OK";
    }

    protected function advise() {
        $aviso_tipo_id = $this->eventos_avisos[$this->evento_tipo_id];

        if ($this->evento_tipo_id == self::$EVENTO_ENTRADA_WAYPOINT ||
            $this->evento_tipo_id == self::$EVENTO_SALIDA_WAYPOINT)
        {
            $this->getAvisosCliente($this->movil_id, $this->cliente_id, $aviso_tipo_id, $this->waypoint_id)
                ->each(function($aviso_cliente) {
                    $info_mail = $this->makeMailWaypoint(
                        $this->dominio, $this->evento_tipo_id, $this->timestamp, $this->waypoint_id
                    );
                    $this->notify($info_mail['subject'], $info_mail['body'], $aviso_cliente->id);
                });
        } else if ($this->evento_tipo_id == self::$EVENTO_DESENGANCHE) {
            $this->getAvisosCliente($this->movil_id, $this->cliente_id, $aviso_tipo_id, self::$FAKE_POSICION_ID)
                ->each(function($aviso_cliente) {
                    $info_mail = $this->makeMailDesenganche(
                        $this->dominio, $this->evento_tipo_id, $this->timestamp, self::$FAKE_POSICION_ID
                    );
                    $this->notify($info_mail['subject'], $info_mail['body'], $aviso_cliente->id);
                });
        }
    }

    protected function isAdvisable($evento_tipo_id) {
        return collect($this->eventos_avisos)->keys()->contains($evento_tipo_id);
    }
}

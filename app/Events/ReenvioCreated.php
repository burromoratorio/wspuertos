<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ReenvioCreated
{
    use SerializesModels;

    /**
     * ID de Reenvio Posicion Host. Es el id único que identifica el envío hacia una plataforma en particular.
     *
     * @var int
     */
    public $id;

    /**
     * Dirección de la plataforma.
     *
     * @var string
     */
    public $host;

    /**
     * Puerto de la plataforma
     *
     * @var int
     */
    public $port;

    /**
     * Mensaje JSON para procesar y enviar.
     *
     * @var string
     */
    public $msg;

    /**
     * Protocolo de transporte de datos (tcp/udp).
     *
     * @var string
     */
    public $proto;

    /**
     * Modo de reenvío (soap-prodtech / caessat).
     *
     * @var string
     */
    public $mode;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($id, $host, $port, $msg, $proto, $mode)
    {
        $this->id = $id;
        $this->host = $host;
        $this->port = $port;
        $this->msg = $msg;
        $this->proto = $proto;
        $this->mode = $mode;
    }
}

<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class AvisoCreated
{
    use SerializesModels;

    /**
     * Aviso ID.
     *
     * @var int
     */
    public $aviso_id;

    /**
     * Asunto del mail.
     *
     * @var string
     */
    public $subject;

    /**
     * Cuerpo del mail.
     *
     * @var string
     */
    public $body;

    /**
     * Direcciones de mail (separadas por coma).
     *
     * @var string
     */
    public $addresses;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($aviso_id, $subject, $body, $addresses)
    {
        $this->aviso_id = $aviso_id;
        $this->subject = $subject;
        $this->body = $body;
        $this->addresses = $addresses;
    }
}

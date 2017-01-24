<?php

namespace App\Listeners;

use App\Events\AvisoCreated;
use Illuminate\Support\Facades\Redis;

class SendMailAviso
{
    /**
     * Handle the event.
     *
     * @param  AvisoCreated  $event
     * @return void
     */
    public function handle(AvisoCreated $event)
    {
        $this->sendAviso($event->aviso_id, $event->subject, $event->body, $event->addresses);
    }

    /**
     * Publica el mail en un canal Redis para un envío asincrónico
     *
     * @param  int  $aviso_id
     * @param  string  $subject
     * @param  string  $body
     * @param  string  $addresses
     * @return void
     */
    protected function sendAviso($aviso_id, $subject, $body, $addresses) {
        if ($addresses == "") throw new \Exception("Falta mail para el aviso: $aviso_id. $subject");
        Redis::publish('mails', json_encode(compact('aviso_id', 'subject', 'body', 'addresses')));
    }
}

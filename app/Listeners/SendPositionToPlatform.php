<?php

namespace App\Listeners;

use App\Events\ReenvioCreated;
use Illuminate\Support\Facades\Redis;

class SendPositionToPlatform
{
    const MODE_CAESSAT='caessat';
    const MODE_PRODTECH='prodtech'; // soap

    /**
     * Handle the event.
     *
     * @param  ReenvioCreated  $event
     * @return void
     */
    public function handle(ReenvioCreated $event)
    {
        $this->publishToRedis($event->id, $event->host, $event->port, $event->msg, $event->proto, $event->mode);
    }

    /**
     * Publica los datos de reenvío en un canal Redis para un envío asincrónico
     *
     * @param  int  $id
     * @param  string  $host
     * @param  int  $port
     * @param  string  $msg
     * @param  string  $proto
     * @param  string  $mode
     * @return void
     */
    protected function publishToRedis($id, $host, $port, $msg, $proto, $mode) {
        if ($mode = static::MODE_CAESSAT) {
            $channel = $proto == 'TCP' ? 'caessat' : 'caessat-udp';
        } else if ($mode = static::MODE_PRODTECH) {
            $channel = 'prodtech';
        } else {
            throw new \Exception("Modo de envío no soportado: ".$mode);
        }

        Redis::publish($channel, json_encode(compact('id', 'host', 'port', 'msg', 'proto')));
    }
}

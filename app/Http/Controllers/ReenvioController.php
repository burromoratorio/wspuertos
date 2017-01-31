<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ReenvioPosicion;
use App\ReenvioPosicionHost;
use App\ReenvioMovil;
use Carbon\Carbon;
use App\Events\ReenvioCreated;
use DB;

class ReenvioController extends Controller
{
    /**
     * Modos de envío soportados
     *
     * @var
     */
    const MODE_CAESSAT='caessat';
    const MODE_PRODTECH='prodtech'; // soap

    /**
     * Estados del envío
     *
     * @var
     */
    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    /**
     * Mapa de ids de rumbos entre SIAC y Prodtech
     *
     * @var
     */
    public $rumbosProdtech = [
        0, // indefinido
        1, // Norte: (siac) 1
        6, // NorthWest: (siac) 2
        4, // West: (siac) 3
        8, // SouthWest: (siac) 4
        2, // South: (siac) 5
        7, // SouthEast: (siac) 6
        3, // East: (siac) 7
        5  // NorthEast: (siac) 8
    ];

    /**
     * Lista los reenvíos
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        return ReenvioPosicion::take(30)->get();
    }

    /**
     * Guarda el reenvío (1 ReenvioPosicion y N ReenvioPosicionHost) en BBDD,
     * y dispara evento para que se comunique a los deamons de envíos, mediante Redis
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function store(Request $request) {
        $this->validate($request, [
            'movil_id' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request) {
            ReenvioMovil::
                where([
                    ['movil_id', $request->input('movil_id')],
                    ['activo', 1],
                ])
                ->each(function ($reenvio_movil) use ($request) {

                    $cadena = $reenvio_movil->modo == static::MODE_CAESSAT ?
                        $this->mkCaessatString($request->all()) :
                        $this->mkSoapString($request->all());

                    $reenvioPosicion = ReenvioPosicion::create([
                        'movil_id' => $reenvio_movil->movil_id,
                        'cadena' => $cadena,
                    ]);

                    $reenvioPosicionHost = ReenvioPosicionHost::create([
                        'reenvio_posicion_id' => $reenvioPosicion->id,
                        'reenvio_host_id' => $reenvio_movil->reenvio_host_id,
                        'estado_envio_id' => static::ESTADO_PENDIENTE,
                    ]);
                    $reenvioHost = $reenvioPosicionHost->reenvio_host;

                    event(new ReenvioCreated(
                        $reenvioPosicionHost->id,
                        $reenvioHost->destino,
                        $reenvioHost->puerto,
                        $reenvioPosicion->cadena,
                        $reenvioHost->protocolo,
                        $reenvio_movil->modo
                    ));
                });
        }, 3);

        return response()->json("OK\n", 201);
    }

    /**
     * Chequea que el valor $field de $name sea exactamente $length bytes de largo
     *
     * @param  string  $name
     * @param  string  $field
     * @param  int  $length
     * @return string
     * @throws Exception
     */
    private function checkExactLength($name, $field, $length) {
        if (strlen($field) != $length)
            throw new \Exception("Longitud incorrecta del campo: ".$name." - ".$field);
        return $field;
    }

    /**
     * Arma cadena de envío según protocolo CAESSAT
     *
     * @param  array  $fields
     * @return string
     */
    private function mkCaessatString(array $fields) {
        //PC251210104844HRA450-34.70557-058.49464018360101+00+00+00
        $cadena =
            "PC".
            $this->checkExactLength("fecha", Carbon::createFromTimestamp($fields['hora'])->format('dmyHis'), 12).
            $this->checkExactLength("patente", substr($fields['patente'], 0, 6), 6).
            $this->checkExactLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9).
            $this->checkExactLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10).
            $this->checkExactLength("velocidad", sprintf("%03d", $fields['velocidad']), 3).
            $this->checkExactLength("sentido", sprintf("%03d", $fields['sentido']), 3).
            $this->checkExactLength("posGpsValida", $fields['posGpsValida'], 1).
            $this->checkExactLength("evento", sprintf("%02d", $fields['evento']), 2).
            $this->checkExactLength("temperatura1", sprintf("%+03d", $fields['temperatura1'] > 99 ? 99 : $fields['temperatura1']), 3).
            $this->checkExactLength("temperatura2", sprintf("%+03d", $fields['temperatura2'] > 99 ? 99 : $fields['temperatura2']), 3).
            $this->checkExactLength("temperatura3", sprintf("%+03d", $fields['temperatura3'] > 99 ? 99 : $fields['temperatura3']), 3).
            "|";
        return $cadena;
    }

    /**
     * Chequea que el valor $field de $name no supere los $length bytes
     *
     * @param  string  $name
     * @param  string  $field
     * @param  int  $length
     * @return string
     * @throws Exception
     */
    private function checkMaxLength($name, $field, $length) {
        if (strlen($field) > $length)
            throw new \Exception("Longitud incorrecta del campo: ".$name." - ".$field);
        return $field;
    }

    /**
     * Arma JSON de envío según especificación WSDL de Prodtech
     *
     * @param  array  $fields
     * @return string
     */
    private function mkSoapString(array $fields) {
        return json_encode([
            "user" => config('app.prodtech_user'),
            "password" => config('app.prodtech_password'),
            "patente" => $this->checkMaxLength("patente", $fields['patente'], 7),
            "gps_datetime" => Carbon::createFromTimestamp($fields['hora'])->format('Y-m-d H:i:s'),
            "latitud" => $this->checkMaxLength("latitud", sprintf("%+09.5f", $fields['latitud']), 9),
            "longitud" => $this->checkMaxLength("longitud", sprintf("%+010.5f", $fields['longitud']), 10),
            "velocidad" => $this->checkMaxLength("velocidad", sprintf("%03d", $fields['velocidad']), 3),
            "temp_congelado" => "0,0",
            "temp_refrigerado" => "0,0",
            "antena" => $fields['antena'],
            "location_name" => "",
            "direccion" => $this->rumbosProdtech[$fields['sentido_id']],
            "analog3" => "",
            "analog4" => "",
            "analog5" => "",
            "analog6" => "",
        ]);
    }

    /**
     * Actualiza estado de reenvio, y si es necesario, dispara evento para volver a reintentar el envío
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return string
     */
    public function update(Request $request, $id) {
        $this->validate($request, [
            'estado_envio_id' => 'required|numeric',
        ]);

        $reenvioPosicionHost = ReenvioPosicionHost::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        if ($estado == static::ESTADO_PENDIENTE) {
            $mode = $request->input('mode', static::MODE_CAESSAT);
            $reenvioHost = $reenvioPosicionHost->reenvio_host;
            event(new ReenvioCreated(
                $reenvioPosicionHost->id,
                $reenvioHost->destino,
                $reenvioHost->puerto,
                $reenvioPosicionHost->reenvio_posicion->cadena,
                $reenvioHost->protocolo,
                $mode
            ));
        }
        $reenvioPosicionHost->estado_envio_id = $estado;
        $reenvioPosicionHost->save();
        return "Update OK";
    }
}

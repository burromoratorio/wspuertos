<?php

namespace App\Http\Controllers;

use App\Exceptions\ExceptionHandler;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Destinatario;

class DestinatarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Destinatario::with(['clientes' =>function($query){
                                  $query->select('razon_social', 'cliente_id');
                            }])->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $destinatario = new Destinatario();

        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|numeric',
            'mail'       => 'required|email|unique:destinatarios,mail',
        ]);

        if ($validator->fails()) {
            $response = [ 'message' => $validator->errors(), 'status' => 400 ];
            return response()->json(['error' => $response], $response['status']);
        } else {
            $destinatario->cliente_id = $request->get('cliente_id');
            $destinatario->mail       = $request->get('mail');

            $destinatario->save();

            $response = $this->getResponseOK($destinatario);
        }

        return response()->json($response,  $response['status']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {

        $destinatario = Destinatario::find($id);

        if ( is_null($destinatario) ) {
            $response = [ 'message' =>  'Registro no encontrado', 'status' => 404 ];
        } else {

            $validator = Validator::make($request->all(), [
                'cliente_id' => 'required|numeric',
                'mail'       => 'required|email|unique:destinatarios,mail,'. $destinatario->id
            ]);

            if ($validator->fails()) {
                $response = [ 'message' => $validator->errors(), 'status' => 400 ];
                return response()->json(['error' => $response], $response['status']);
            } else {

                $input = $request->all();

                $destinatario->fill($input)->save();

                return response()->json(['id' => $destinatario->id,
                                         'mail' => $destinatario->mail,
                                         'cliente_id' => $destinatario->cliente_id,
                                         'message' => 'El Destinatario ha sido actualizado satisfactoriamente',
                                         'status' => 200
                                        ]);
            }

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $destinatario = Destinatario::find($id);

        if ( is_null($destinatario) ) {

            $response = [ 'message' =>  'Registro no encontrado', 'status' => 404 ];

        } else {

            $destinatario->delete();

            $response = $this->getResponseOK($destinatario, 'delete');
        }

        return response()->json($response,  $response['status']);
    }

    /**
     * Returns an array with the response
     *
     * @param  int      $$destinatario
     * @param  string   $action
     * @return array
     */
    private function getResponseOK($destinatario, $action = 'new')
    {
        $response = ['id' => $destinatario->id,
                     'mail' => $destinatario->mail,
                     'cliente_id' => $destinatario->cliente_id,
                     'message' => '',
                     'status' => 200
                    ];

        if ( $action == 'update' ) {
            $response['message'] = 'El Destinatario ha sido actualizado satisfactoriamente';
        } elseif ( $action == 'delete' ) {
            $response['message'] = 'El Destinatario ha sido eliminado satisfactoriamente';
        } else {
            $response['message'] = 'El Destinatario ha sido creado satisfactoriamente';
        }

        return $response;
    }

}

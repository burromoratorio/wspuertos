<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class ProductoController extends Controller
{
   public function create(Request $request){
    	$method = $request->method();
    	if ($request->isMethod('post')) {
			$jsonReq = $request->json()->all();
			var_dump($jsonReq);
			$name = "clienteJson";
		}else{
			$name = "clienteSinJson";
		}
		$response = ["viaje" => [$name]];
    	return $response;
    }
}

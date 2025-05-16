<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    /** Metodo para regresar todos los usuarios registrados */
    public function listaUsuarios(){
        // Obtener todos los usuarios de la BD usando el modelo para buscarlos
        $usuarios = Usuario::all();

        // Regresar un error si no se encontraron usuarios
        if($usuarios->isEmpty()){
            return response()->json(['msgError' => 'Error: No hay usuarios registrados.'], 404);
        }
        // Regresar la lista de usuarios encontrados
        return response()->json(['results' => $usuarios], 200);
    }
    
    /* Ejemplo de consulta con filtrado de datos usando Eloquent (ORM de Laravel):
    $flights = Flight::where('active', 1)
        ->orderBy('name')
        ->take(10)
        ->get(); */

    /** Metodo para buscar un usuario */
    public function buscarUsuario(Request $consulta){
        // Validar la direccion de correo
        $validador = Validator::make($consulta->all(), ['correo' => 'required|email']);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: No se ingresó una dirección de correo valida, favor de revisarla.'], 500);

        // Buscar y obtener el usuario en la BD usando el correo ingresado
        $infoRes = Usuario::where('Correo', '=', $consulta->correo)->first();

        // Regresar un error si no se encontró el usuario
        if(!$infoRes)
            return response()->json(['msgError' => 'Error: Favor de revisar la información ingresada.'], 500);

        // Regresar la información encontrada en la BD
        return response()->json(['results' => [$infoRes->Correo, $infoRes->Contra]], 200);
    }
}

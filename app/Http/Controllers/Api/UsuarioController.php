<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /** Metodo para regresar todos los usuarios registrados 
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function listaUsuarios(){
        // Obtener todos los usuarios de la BD usando el modelo para buscarlos
        $usuarios = Usuario::all();

        // Regresar un error si no se encontraron usuarios
        if($usuarios->isEmpty())
            return response()->json(['msgError' => 'Error: No hay usuarios registrados.'], 404);

        // Regresar la lista de usuarios encontrados
        return response()->json(['results' => $usuarios], 200);
    }
    
    /* Ejemplo de consulta con filtrado de datos usando Eloquent (ORM de Laravel):
    $flights = Flight::where('active', 1)
        ->orderBy('name')
        ->take(10)
        ->get();
    Nota para la elaboracion de la consultas:
    Se debe usar get() al final si se busca obtener multiples registros y first() en caso de requerir un solo registro. */

    /** Metodo para buscar un usuario de forma normal, como acceder
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function buscarUsuario(Request $consulta){
        // Validar la direccion de correo enviada desde el cliente
        $validador = Validator::make($consulta->all(), [
            'correo' => 'required|email'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: No se ingresó una dirección de correo valida, favor de revisarla.'], 500);

        // Buscar y obtener el usuario en la BD
        $infoRes = Usuario::where('Correo', '=', $consulta->correo)->select(['Correo', 'Contra'])->first();

        // Regresar un error si no se encontró el usuario
        if(!$infoRes)
            return response()->json(['msgError' => 'Error: Favor de revisar la información ingresada.'], 500);

        // Regresar la información encontrada en la BD
        return response()->json(['results' => $infoRes], 200);
    }

    /** Metodo para buscar un usuario para recuperación de acceso 
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function buscarUsuarioRecu(Request $consulta){
        // Validar los campos enviados desde el cliente
        $validador = Validator::make($consulta->all(), [
            'codBus' => 'required|regex:/^(?!.*\s{2,})([A-Z]{3}[-]?[\d]{4})$/',
            'nomBus' => 'required|regex:/^(?!.*\s{2,})([a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]+(?:\s[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]+)*)$/',
            'apePatBus' => 'required|regex:/^(?!.*\s{2,})([a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]+)$/',
            'apeMatBus' => 'required|regex:/^(?!.*\s{2,})([a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]+)$/',
            'correoBus' => 'required|email'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: Uno de los campos no cumple con las normas para el ingreso de información. Favor de revisar la información ingresada.'], 500);

        // Buscar y obtener el usuario en la BD
        $infoRes = Usuario::where([
            ['Cod_User', '=', $consulta->codBus],
            ['Nombre', '=', $consulta->nomBus],
            ['Ape_Pat', '=', $consulta->apePatBus],
            ['Ape_Mat', '=', $consulta->apeMatBus],
            ['Correo', '=', $consulta->correoBus]
        ])->select(['Cod_User', 'Correo'])->first();

        // Regresar un error si el no se encontro el usuario
        if(!$infoRes)
            return response()->json(['msgError' => 'Error: Favor de revisar la información ingresada.'], 500);

        // Regresar la información encontrada en la BD
        return response()->json(['results' => $infoRes], 200);
    }

    /** Metodo para actualizar la fecha del ultimo acceso 
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function nueValUltiAcc(Request $consulta){
        // Primero se verifica que el usuario en cuestion exista
        $usuario = Usuario::where('Correo', '=', $consulta->correo)->select(['Cod_User', 'Correo'])->first();

        // Retornar error si el validador falla
        if(!$usuario)
            return response()->json(['msgError' => 'Error: El usuario que se referencia no existe.'], 500);

        // Validar los campos enviados desde el cliente
        $validador = Validator::make($consulta->all(), [
            'correo' => 'required|email',
            'fechaUltiAcc' => 'required',
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: Actualización de ultimo acceso corrompida.'], 500);

        // Establecer el valor del campo ultimo acceso si la consulta desde el cliente trae los campos requeridos
        if($consulta->has('correo') && $consulta->has('fechaUltiAcc'))
            $usuario->UltimoAcceso = $consulta->fechaUltiAcc;
        
        // Actualizar el valor
        $usuario->save();

        // Regresar el mensaje de consulta realizada
        return response()->json(['results' => 'La fecha de acceso fue actualizada.'], 200);
    }

    /** Metodo para actualizar la contraseña 
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function nueValContra(Request $consulta){
        // Primero se verifica que el usuario en cuestion exista
        $usuario = Usuario::where([
            ['Cod_User', '=', $consulta->codUsu],
            ['Nombre', '=', $consulta->nomPerso]
        ])->select(['Cod_User', 'Correo'])->first();

        // Retornar error si el validador falla
        if(!$usuario)
            return response()->json(['msgError' => 'Error: El usuario no existe.'], 404);

        // Validar los campos enviados desde el cliente
        $validador = Validator::make($consulta->all(), [
            'codUsu' => 'required|regex:/^(?!.*\s{2,})([A-Z]{3}[-]?[\d]{4})$/',
            'nomPerso' => 'required|regex:/^(?!.*\s{2,})([a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]+(?:\s[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]+)*)$/',
            'nueContraVal' => 'required|regex:/^(?!\s+$)(?=\S{6,20}$)(?=.*[A-ZÁÉÍÓÚÜÑ])(?=.*[a-záéíóúüñ])(?=.*\d)(?=.*[^\w\s])[^\s]{6,20}$/u'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: Favor de revisar la información que utilizó para la actualización de datos.'], 500);

        // Establecer el valor del campo contraseña hasheado (por defecto con 12 rondas) si la consulta desde el cliente trae los campos requeridos
        if($consulta->has('codUsu') && $consulta->has('nomPerso') && $consulta->has('nueContraVal'))
            $usuario->Contra = Hash::make($consulta->nueContraVal);
        
        // Actualizar el valor
        $usuario->save();

        // Regresar el mensaje de consulta realizada
        return response()->json(['results' => 'La información de '.$consulta->nomPerso.' fue actualizada exitosamente.'], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Link_Recu;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\UsuarioController;
use App\Helpers\GenLinksHelper;

class LinkRecuController extends Controller
{
    /** Metodo para obtener todos los enlaces de recuperación guardados */
    public function listaEnlacesRecu(){
        // Obtener todos los enlaces de recuperación en el sistema
        $enlacesRecu = Link_Recu::all();

        // Regresar un error si no se encontraron enlaces
        if($enlacesRecu->isEmpty())
            return response()->json(['msgError' => 'Error: No hay enlaces de recuperación.'], 404);

        // Regresar la lista de sensores encontrados
        return response()->json(['results' => $enlacesRecu], 200);
    }

    /** Metodo para obtener la ruta del sistema en base al enlace enviado en el correo */
    public function obteRutaActuSis(Request $consulta){
        // Validar el link enviado en la consulta desde el cliente
        $validador = Validator::make($consulta->all(), [
            'linkCorreo' => 'required|regex:/^[a-zA-Z\d-]{1,8}$/'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: Información no encontrada.'], 500);

        // Buscar la ruta de recuperación en la BD
        $rutaSis = Link_Recu::where('Link_Correo', '=', $consulta->linkCorreo)->value('Ruta_Sistema');

        // Regresar un error si el no se encontro el usuario
        if(!$rutaSis)
            return response()->json(['msgError' => 'Error: Información no encontrada, la solicitud en cuestión no existe o ya fue realizada, favor de generar otra.'], 500);

        // Regresar la información encontrada en la BD
        return response()->json(['results' => $rutaSis], 200);
    }

    /** Metodo para generar el link de recuperación, guardarlo en la BD y enviar el correo de recuperación */
    public function crearUsuRecu(Request $consulta){
        // NOTA Futura: La estructura de la recuperación cambiará ligeramente, puesto que ya no será necesario crear multiples elementos alternando entre back y front (generar el codigo, guardarlo en la bd y enviar el correo) para hacerlo.
        /* La recuperación cambiará a la siguiente forma: 
        1.- La funcion recuContra será invocada desde el front y enviará los siguientes elementos: codigo, nombre, apePater, apeMater, correo.
        2.- Se buscará al usuario en el sistema usando el metodo buscarUsuarioRecu del controlador de usuarios para saber si se procederá con la recuperación.
        3.- Si se encuentra, se generará el link de recuperación aleatorio en el sistema que se trasladará al back, para ello se usará el helper creado.
        3.- Una vez generado el link aleatorio, se procedera con el guardado del mismo.
        4.- Una vez guardado el link en la bd, se procederá con el envio del correo de recuperación al cliente.
        5.- Al terminar el proceso se le regresará al cliente la respuesta obtenida del envio
        NOTA: Si en algún punto el proceso se corrompe o hay errores, se regresarán errores como en las demas funciones realizadas */

        /* Nombres de los parametros enviados desde el cliente:
        'codBus', 'nomBus', 'apePatBus', 'apeMatBus', 'correoBus' */

        // Crear un objeto del controlador usuario y usar el metodo de busqueda de usuario recuperacion
        $busUsuaRecu = app(UsuarioController::class)->buscarUsuarioRecu($consulta);
        
        // Regresar un error si no se encontro el usuario
        if(empty($busUsuaRecu->getContent()))
            return response()->json(['msgError' => 'Error: No hay usuario relacionado con la información ingresada.'], 404);
        
        // Decodificar la respuesta de la busqueda de usuario como arreglo asociativo
        $infoUsuario = json_decode($busUsuaRecu, true);

        // Si se obtuvo un error de busqueda se regresará un error de procesamiento del sistema
        if(array_key_exists('msgError', $infoUsuario))
            return response()->json(['msgError' => $infoUsuario['msgError']], 404);

        // Crear el objeto helper y usar el metodo de generación de links aleatorios
        $linkRecuGen = app(GenLinksHelper::class)->generadorLinks();
        
        // Guardar el link aleatorio en la base de datos asi como la ruta del sistema a la que apuntara el enrutamiento dinamico
        $guardaLink = Link_Recu::create([
            'Link_Correo' => $linkRecuGen,
            'Ruta_Sistema' => $consulta->codBus."/".$consulta->nomBus
        ]);

        // Regresar un error si no se pudo registrar el link de recuperación
        if(!$guardaLink)
            return response()->json(['msgError' => 'Error: Proceso de recuperación interrumpido. Favor de intentar nuevamente.'], 500);

    }
}

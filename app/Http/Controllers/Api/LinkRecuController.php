<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Link_Recu;
use Illuminate\Support\Facades\Validator;

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
            'linkCorreo' => 'required|regex:/^[a-zA-Z\d-]{1,8}$/
'
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
}

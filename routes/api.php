<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\RegistroSensorController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\TipoSensorController;
use App\Http\Controllers\Api\LinkRecuController;

Route::get('/token', function (Request $request) {
    $token = $request->session()->token();
    $token = csrf_token();
 
    // Regresar un error si no se generó el token
    if(!$token)
        return response()->json(['msgError' => 'Error: Favor de revisar la información ingresada.'], 500);

    // Regresar el token
    return response()->json(['results' => $token], 200);
});

/*Route::get("/busUser", function(){
    return "Nombre del endpoint viejo: getBusUs";
});*/
Route::get("/busUsuario", [UsuarioController::class, "buscarUsuario"]);

// Este endpoint sera absorvido por el proceso del nuevo endpoint /crearRecuAccUs
//Route::get("/busUsRecu", [UsuarioController::class, "buscarUsuarioRecu"]);

Route::get("/histoComple", [RegistroSensorController::class, "listaRegistroSensores"]);

/*Route::get("/histoEspeci", function() {
    return "Nombre del endpoint viejo: getHistoEspeci";
});*/
Route::get("/histoEspeci", [RegistroSensorController::class, "listaRegistroEspeci"]);

Route::get("/sensoresRegi", [SensorController::class, "listaSenRegi"]);

Route::get("/sensoresNoRegi", [TipoSensorController::class, "listaSensoresNoRegi"]);

Route::get("/linkActuContra", [LinkRecuController::class, "obteRutaActuSis"]);

// Este endpoint sera absorvido por el proceso del nuevo endpoint /crearRecuAccUs
/*Route::post("/enviCorRecu", function(){
    return "Nombre del endpoint viejo: postCorreoRecu";
});*/

Route::post("/registroSen", [SensorController::class, 'regiSensor']);

Route::post("/crearRecuAccUs", [LinkRecuController::class, 'crearUsuRecu']);

Route::patch("/actUltiAcc", [UsuarioController::class, "nueValUltiAcc"]);

Route::patch("/actuContra", [UsuarioController::class, "nueValContra"]);

Route::delete("/borLinkRecuper", [LinkRecuController::class, "borLinkRecu"]);

// Endpoint de prueba para probar la conexion con los controladores
Route::get("/listaUsuarios", [UsuarioController::class, "listaUsuarios"]);
// Obtener el listado de todos los sensores
Route::get("/fullRegi", [RegistroSensorController::class, "listaRegistroSensores"]);
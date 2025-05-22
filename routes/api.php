<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\RegistroSensorController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\TipoSensorController;
use App\Http\Controllers\Api\LinkRecuController;

/*Route::get("/busUser", function(){
    return "Nombre del endpoint viejo: getBusUs";
});*/
Route::get("/busUsuario", [UsuarioController::class, "buscarUsuario"]);

Route::get("/busUsRecu", [UsuarioController::class, "buscarUsuarioRecu"]);

Route::get("/histoComple", [RegistroSensorController::class, "listaRegistroSensores"]);

Route::get("/histoEspeci", function() {
    return "Nombre del endpoint viejo: getHistoEspeci";
});

Route::get("/sensoresRegi", [SensorController::class, "listaSenRegi"]);

Route::get("/sensoresNoRegi", [TipoSensorController::class, "listaSensoresNoRegi"]);

Route::get("/linkActuContra", [LinkRecuController::class, "obteRutaActuSis"]);

Route::post("/enviCorRecu", function(){
    return "Nombre del endpoint viejo: postCorreoRecu";
});

Route::post("/registroSen", function(){
    return "Nombre del endpoint viejo: postNueSens";
});

Route::post("/crearLinkRecu", function(){
    return "Nombre del endpoint viejo: postRutaActuPass";
});

Route::patch("/actUltiAcc", [UsuarioController::class, "nueValUltiAcc"]);

Route::patch("/actuContra", [UsuarioController::class, "nueValContra"]);

Route::delete("/borLinkActuContra", function(){
    return "Nombre del endpoint viejo: delRutaActuPass";
});

// Endpoint de prueba para probar la conexion con los controladores
Route::get("/listaUsuarios", [UsuarioController::class, "listaUsuarios"]);
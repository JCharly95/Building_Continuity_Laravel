<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;

/*Route::get("/busUser", function(){
    return "Nombre del endpoint viejo: getBusUs";
});*/
Route::get("/busUser", [UsuarioController::class, "buscarUsuario"]);

Route::get("/busUsRecu", function(){
    return "Nombre del endpoint viejo: getBusUsRecuPass";
});

Route::get("/histoComple", function(){
    return "Nombre del endpoint viejo: getHistoGen";
});

Route::get("/histoEspeci", function() {
    return "Nombre del endpoint viejo: getHistoEspeci";
});

Route::get("/listaSenRegi", function() {
    return "Nombre del endpoint viejo: getSensRegi";
});

Route::get("/listaSenNoRegi", function(){
    return "Nombre del endpoint viejo: getSensNoRegi";
});

Route::get("/linkActuPass", function(){
    return "Nombre del endpoint viejo: getRutaActuPass";
});

Route::post("/mandCorRecu", function(){
    return "Nombre del endpoint viejo: postCorreoRecu";
});

Route::post("/regiSen", function(){
    return "Nombre del endpoint viejo: postNueSens";
});

Route::post("/genRutaRecu", function(){
    return "Nombre del endpoint viejo: postRutaActuPass";
});

Route::patch("/actUltiAcc", function(){
    return "Nombre del endpoint viejo: postUltiAcc";
});

Route::patch("/actuContra", function(){
    return "Nombre del endpoint viejo: postActuPass";
});

Route::delete("/linkActuPass", function(){
    return "Nombre del endpoint viejo: delRutaActuPass";
});

// Endpoint de prueba para probar la conexion con los controladores
Route::get("/listaUsuarios", [UsuarioController::class, "listaUsuarios"]);
<?php

namespace App\Helpers;

class GenLinksHelper
{
    /** Funcion para generar cadenas de texto aleatorias implementadas en los links de recuperación
     * @return string Cadena de texto utilizada para el link de recuperacion */
    public function generadorLinks(){
        $oriCarLinks = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-";
        $linkLongi = 8;
        $linkRes = "";

        // Ciclo para generar una cadena de texto con caracteres aleatorios y una longitud de 8 caracteres; NOTA: Los caracteres pueden aparecer en mas de una ocasión
        for($cont = 0; $cont < $linkLongi; $cont++){
            $linkRes .= $oriCarLinks[floor((mt_rand() / mt_getrandmax()) * strlen($oriCarLinks))];
        }
        
        return $linkRes;
    }
}

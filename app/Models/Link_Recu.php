<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link_Recu extends Model
{
    /** Tabla asociada al modelo
     * @var string */
    protected $table = "links_recuperacion";

    /** Clave primaria de la tabla
     * @var string */
    protected $primaryKey = "ID_Link";

    /** Variable de timestamps para la tabla
     * @var bool */
    public $timestamps = false;

    /** Atributos visibles y asignados en consultas masivas 
     * @var list<string> */
    protected $fillable = [
        'Link_Correo',
        'Ruta_Sistema'
    ];
}

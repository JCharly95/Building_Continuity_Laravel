<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    /** Nombre de la tabla asociada al modelo
     * @var string */
    protected $table = "usuarios";

    /** Clave primaria de la tabla
     * @var string */
    protected $primaryKey = "ID_User";

    /** Determinar si la tabla tendra timestamps en sus registros
     * @var bool */
    public $timestamps = false;

    /** Los atributos que pueden ser asignados en consultas de datos masivas.
     * @var list<string> */
    protected $fillable = [
        'Cod_User',
        'Ape_Pat',
        'Ape_Mat',
        'Nombre',
        'Correo',
        'Contra',
        'UltimoAcceso'
    ];
}

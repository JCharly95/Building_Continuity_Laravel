<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    /** Tabla asociada al modelo
     * @var string */
    protected $table = "sensor";

    /** Clave primaria de la tabla
     * @var string */
    protected $primaryKey = "ID_Sensor";

    /** Variable de timestamps para la tabla
     * @var bool */
    public $timestamps = false;

    /** Atributos visibles y asignados en consultas masivas 
     * @var list<string> */
    protected $fillable = [
        'Nombre',
        'Tipo_ID'
    ];
}

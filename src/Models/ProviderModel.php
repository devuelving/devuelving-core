<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderModel extends Model
{
    use SoftDeletes;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'provider';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'active', 'profit_margin', 'email', 'phone', 'data', 'web',
    ];

    /**
     * Función para obtener los datos desde la columna data que esta en formato json
     *
     * @param string $data
     * @return void
     */
    public function getData($data)
    {
        $providerData = json_decode($this->data);
        return $providerData->$data;
    }
}

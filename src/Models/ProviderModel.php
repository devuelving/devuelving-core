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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'shipping_type', 'delivery_term', 'active', 'profit_margin', 'email', 'phone', 'data', 'web',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
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

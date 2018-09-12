<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddressModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'address';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'customer', 'street', 'number', 'floor', 'door', 'town', 'province', 'postal_code', 'country', 'status'
    ];
}

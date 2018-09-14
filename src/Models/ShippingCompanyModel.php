<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class ShippingCompanyModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_company';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'image',
    ];
}

<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class CartModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer', 'product', 'units',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];
}

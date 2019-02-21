<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class MyShippingFeesModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'my_shop_shipping_fees';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'franchise', 'name', 'rate_2', 'rate_3', 'rate_5', 'rate_7', 'rate_10', 'rate_15', 'rate_20', 'rate_30', 'rate_40'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];
}

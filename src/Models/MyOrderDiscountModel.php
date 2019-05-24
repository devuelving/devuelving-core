<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class MyOrderDiscountModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'my_shop_order_discounts';

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
        'order', 'type', 'discount_voucher_data', 'discount_value',
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
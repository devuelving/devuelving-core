<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class CustomerBalance extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_balances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount', 'status', 'type', 'order',
    ];
}

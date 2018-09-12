<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_methods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'porcentual', 'fixed',
    ];
}

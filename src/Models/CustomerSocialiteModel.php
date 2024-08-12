<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class CustomerSocialiteModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_socialite';

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
        'customer', 'franchise', 'provider', 'provider_user_id'
    ];
}

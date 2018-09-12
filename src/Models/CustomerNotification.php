<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'franchise', 'customer', 'subject', 'content', 'status',
    ];
}

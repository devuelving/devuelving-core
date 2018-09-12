<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'suggestions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'franchise', 'customer', 'subject', 'content', 'status',
    ];
}

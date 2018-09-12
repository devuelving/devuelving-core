<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseNumberModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_numbers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'headquarter', 'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at',
    ];
}

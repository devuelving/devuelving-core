<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class ProductParentModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_parent';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'franchise',
    ];
}

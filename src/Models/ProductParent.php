<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductParent extends Model
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

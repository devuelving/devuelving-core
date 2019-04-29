<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class CategoryEquivalenceModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_equivalence';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider', 'category', 'category_privider',
    ];

}

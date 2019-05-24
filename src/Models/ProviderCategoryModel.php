<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class ProviderCategoryModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'provider_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider', 'provider_category_id', 'provider_category_name', 'category_id', 'category_name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 
    ];
}

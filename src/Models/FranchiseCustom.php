<?php

namespace App;

use App\Franchise;
use Illuminate\Database\Eloquent\Model;

class FranchiseCustom extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_custom';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'var', 'value', 'franchise'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id','created_at','updated_at'
    ];
}

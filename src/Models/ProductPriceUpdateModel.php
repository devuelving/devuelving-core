<?php

namespace devuelving\core;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ProductPriceUpdateModel extends Model
{
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_price_update';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'product', 'new_price_cost', 'old_price_cost',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

   
}

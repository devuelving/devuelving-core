<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

use App\Product;

use devuelving\core\WishlistModel;
use devuelving\core\FavoritesModel;

class WishlistDetailModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wishlist_details';

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
        'wishlist_id', 'product_id', 'units',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at',
    ];

    /**
     * Relationship belongs to favorite
     */
    public function favorite()
    {
        return $this->hasOne(FavoritesModel::class, 'product_id', 'product_id' )->where( 'customer', auth()->user()->id );
    }

    /**
     * Relationship belongs to product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship belongs to wishlist
     */
    public function wishlist()
    {
        return $this->belongsTo(WishlistModel::class);
    }
    
    
}

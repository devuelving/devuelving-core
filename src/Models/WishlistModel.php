<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

use devuelving\core\WishlistDetailModel;

class WishlistModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wishlists';

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
        'code', 'customer', 'type', 'name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * Relationship
     */
    public function wishes()
    {
        return $this->hasMany(WishlistDetailModel::class, 'wishlist_id', 'id');
    }
}

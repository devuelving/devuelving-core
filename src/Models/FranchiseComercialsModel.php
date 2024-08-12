<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseComercialsModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_comercials';

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
        'franchise', 'name', 'code', 'id', 'commission', 'type_commission', 'promo_days', 'options', 'email', 'status', 'supervisor', 'parent', 'phone'
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
     * Relationship comercial customer hasMany
     */
    public function comercialCustomer()
    {
        return $this->hasMany('devuelving\core\CustomerModel', 'comercial', 'code');
    }
}

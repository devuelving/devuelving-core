<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCouponModel extends Model
{

    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discount_coupons';

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
        'id', 'franchise', 'code', 'status', 'type', 'amount', 'first_purchase', 'start_date', 'end_date', 'minimum_amount', 'limit_user', 'limit_purchase', 'clients', 'products', 'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];
    
    public function toArray()
    {
        return [
            'code' => $this->code, 
            'status' => $this->status, 
            'type' => $this->type, 
            'amount' => $this->amount, 
            'first_purchase' => $this->first_purchase, 
            'start_date' => $this->start_date, 
            'end_date' => $this->end_date, 
            'minimum_amount' => $this->minimum_amount, 
            'limit_user' => $this->limit_user, 
            'limit_purchase' => $this->limit_purchase, 
            'clients' => $this->clients, 
            'products' => $this->products
        ];
    }
}

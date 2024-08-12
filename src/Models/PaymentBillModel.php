<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class PaymentBillModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_bills';

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
        'payment', 'full_name', 'nif', 'address',
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
     * Relationship paymentbill hasMany payments
     */
    public function payments()
    {
        return $this->hasMany('devuelving\core\PaymentModel', 'code', 'id');
    }
}

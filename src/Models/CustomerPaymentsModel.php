<?php

namespace devuelving\core;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CustomerPaymentsModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_payments';

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
        'customer', 'amount', 'status', 'type', 'type_payment', 'payment_method', 'payment_method_cost', 'payment_method_data', 'payment_date', 'expires_date'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    protected $appends = ['active'];

    public function getActiveAttribute()
    {
        return Carbon::now()->between(Carbon::parse($this->payment_date), Carbon::parse($this->expires_date)) && $this->status == 1;
    }

    /**
     * Get the customer that owns the payment.
     */
    public function getCustomer()
    {
        return $this->belongsTo(CustomerModel::class, 'customer', 'id');
    }

    function getPaymentMethod()
    {
        return $this->belongsTo(MyPaymentMethodModel::class, 'payment_method', 'id');
    }
}

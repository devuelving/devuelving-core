<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseRecurrentPaymentsModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_recurrent_payments';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_identifier', 'name', 'email', 'nif', 'monthly_amount', 'amount_paid', 'completed_payments', 'total_payments', 'next_payment'
    ];
  }

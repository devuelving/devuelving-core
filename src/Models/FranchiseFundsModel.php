<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseFundsModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_funds';

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
        'amount', 'status', 'type', 'concept', 'franchise', 'payment_id',
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
     * Returns available funds for a given Franchise
     *
     * @param int $franchise
     * @return void
     */
    public static function getFranchiseFunds($franchise)
    {
        $totalfunds = FranchiseFundsModel::where('franchise', $franchise)->where('type', 1)->where('status', 2)->sum('amount');
        $used = FranchiseFundsModel::where('franchise', $franchise)->where('type', 2)->where('status', 2)->sum('amount');
        $reinbursements = FranchiseFundsModel::where('franchise', $franchise)->where('type', 3)->where('status', 2)->sum('amount');
        return $totalfunds - $used + $reinbursements;
    }
}

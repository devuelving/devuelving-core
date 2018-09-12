<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderBills extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_bills';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order', 'full_name', 'nif', 'address', 'concept',
    ];
}
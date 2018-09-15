<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseContactDataModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_contact_data';

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
        'franchise', 'type', 'name', 'surname', 'nif', 'street', 'number', 'floor', 'door', 'town', 'province', 'postal_code', 'country', 'phone', 'email', 'notes',
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

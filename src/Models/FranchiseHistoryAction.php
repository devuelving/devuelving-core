<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseHistoryAction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_history_actions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'franchise', 'content', 'attachment', 'agent',
    ];
}

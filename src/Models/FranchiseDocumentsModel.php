<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class FranchiseDocumentsModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'name', 'path', 'franchise',
    ];
}

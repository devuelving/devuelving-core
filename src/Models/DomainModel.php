<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class DomainModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'domains';

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
        'franchise', 'name', 'status', 'tld', 'domain_id', 'ts_expir', 'ts_create', 'renewable', 'renewal_mode', 'modify_block', 'transfer_block', 'whois_privacy', 'view_whois', 'authcode_check', 'service_associated', 'tag', 'ownerverification'
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
     * Relationship belongsTo franchise
     */
    public function franchise()
    {
        return $this->belongsTo('devuelving\core\FranchiseModel', 'franchise', 'id');
    }
}

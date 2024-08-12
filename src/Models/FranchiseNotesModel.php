<?php

namespace devuelving\core;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FranchiseNotesModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_notes';

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
        'type', 'franchise', 'note', 'agent',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * Returns name of the user who made the note
     *
     * @return string
     */
    public function userName()
    {
        $user = User::find($this->agent);
        return $user->name;
    }

    /**
     * Relationship product custom hasOne
     */
    public function agentData()
    {
        //return $this->hasMany('devuelving\core\ProductCustomModel', 'product', 'id');
        return $this->belongsTo(AdminModel::class, 'agent', 'id');
    }
}

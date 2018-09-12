<?php

namespace devuelving\core;

use devuelving\core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderNotes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order', 'note', 'user',
    ];

    /**
     * Returns name of the user who made the note
     *
     * @return string
     */
    public function userName()
    {
        $user = User::find($this->user);
        return $user->name;
    }
}

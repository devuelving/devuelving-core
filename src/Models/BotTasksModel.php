<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class BotTasksModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bot_tasks';

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
        'bot', 'start_date', 'stop_date', 'result', 'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at',
    ];

}

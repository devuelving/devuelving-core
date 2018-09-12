<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingpageVideosModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'landingpage_videos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url', 'franchise',
    ];
}

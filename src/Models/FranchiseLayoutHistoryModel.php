<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use devuelving\core\FranchiseLayoutModel;

class FranchiseLayoutHistoryModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_layout_histories';

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
        'id', 'layout', 'content', 'author'
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
     * Crea la copia de seguridad del layout
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param FranchiseLayoutModel $layout
     * @param User $user
     * @return void
     */
    public function makeBackup($layout, $user)
    {
        $layout = FranchiseLayoutModel::find($layout);
        $franchiseLayoutHistory = new FranchiseLayoutHistoryModel();
        $franchiseLayoutHistory->layout = $layout->id;
        $franchiseLayoutHistory->content = $layout->content;
        $franchiseLayoutHistory->author = $user->id;
        $franchiseLayoutHistory->save();
    }
}

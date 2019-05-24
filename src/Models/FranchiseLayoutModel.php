<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use devuelving\core\FranchiseLayoutHistoryModel;

class FranchiseLayoutModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise_layouts';

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
        'id', 'franchise', 'type', 'name', 'content'
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
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        self::updating(function ($franchiseLayout) {
            $franchiseLayoutHistory = new FranchiseLayoutHistoryModel();
            $franchiseLayoutHistory->makeBackup($franchiseLayout->id, auth()->user());
        });
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'franchise' => $this->franchise,
            'type' => $this->type,
            'name' => $this->name,
            'content' => $this->getContent()
        ];
    }

    /**
     * Decodifica el contenido del layout en json
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getContent()
    {
        return json_decode($this->content);
    }
}

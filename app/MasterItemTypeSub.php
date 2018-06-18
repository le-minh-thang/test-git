<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterItemTypeSub extends Model
{
    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
    public $timestamps = false;
    /**
     * The database name used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql_other';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'master_item_type_sub';

    public function itemSubSides()
    {
        return $this->hasMany(MasterItemTypeSubSide::class, 'color_id', 'id');
    }
}

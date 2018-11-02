<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrilabMasterItemType extends Model
{
    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The database name used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql_orilab';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'master_item_type';

    public function itemSubs()
    {
        return $this->hasMany(OrilabMasterItemTypeSub::class, 'item_type', 'id');
    }
}

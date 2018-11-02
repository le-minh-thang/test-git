<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrilabMasterItemTypeSub extends Model
{
    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'master_item_type_sub';

    /**
     * The database name used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql_orilab';
}

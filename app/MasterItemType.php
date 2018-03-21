<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterItemType extends Model
{
    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
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
    protected $table = 'master_item_type';

    public function itemSizes()
    {
        return $this->hasMany(MasterItemTypeSize::class, 'item_type', 'id');
    }

    public function itemSubs()
    {
        return $this->hasMany(MasterItemTypeSub::class, 'item_type', 'id');
    }

    public function printtyProduct()
    {
        return $this->hasOne(PrinttyProduct::class, 'product_code', 'item_code');
    }

    public function printtyProductColors()
    {
        return $this->hasMany(PrinttyProductColor::class, 'product_code', 'item_code');
    }

    public function printtyProductColorSides()
    {
        return $this->hasMany(PrinttyProductColorSide::class, 'product_code', 'item_code');
    }

    public function printtyProductSizes()
    {
        return $this->hasMany(PrinttyProductSize::class, 'product_code', 'item_code');
    }
}

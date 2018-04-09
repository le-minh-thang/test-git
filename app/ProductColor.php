<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products_colors';

     protected $guarded = [];

    public function productColorSides()
    {
        return $this->hasMany(ProductColorSide::class)->where('is_deleted', 0)->orderBy('id', 'asc');;
    }

    public function productColorSideMain()
    {
        return $this->hasOne(ProductColorSide::class)->where('is_deleted', 0)->where('is_main', 1)->orderBy('id', 'asc');;
    }
}

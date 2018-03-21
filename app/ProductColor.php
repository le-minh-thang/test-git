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
        return $this->hasMany(ProductColorSide::class);
    }
}

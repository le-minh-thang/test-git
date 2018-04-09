<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products';

    protected $guarded = [];

    public function productSizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function productColors()
    {
        return $this->hasMany(ProductColor::class)->where('is_deleted', 0)->orderBy('id', 'asc');
    }

    public function productColorMain()
    {
        return $this->hasOne(ProductColor::class)->where('is_deleted', 0)->where('is_main', 1)->orderBy('id', 'asc');;
    }

}

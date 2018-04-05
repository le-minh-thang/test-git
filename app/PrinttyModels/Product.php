<?php

namespace App\PrinttyModels;

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

    protected $connection = 'mysql_printty';

    public function productSizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function productColors()
    {
        return $this->hasMany(ProductColor::class);
    }

}

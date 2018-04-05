<?php

namespace App\PrinttyModels;

use Illuminate\Database\Eloquent\Model;

class ProductColorSide extends Model
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $connection = 'mysql_printty';

    protected $table = 'products_colors_sides';
}

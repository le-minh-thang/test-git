<?php

namespace App\PrinttyModels;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products_sizes';

    protected $connection = 'mysql_printty';
}

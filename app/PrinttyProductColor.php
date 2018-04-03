<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrinttyProductColor extends Model
{
    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
    protected $guarded = [];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'printty_products_colors';
}

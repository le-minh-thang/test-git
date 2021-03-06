<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UpTPrinttyProductColorSide extends Model
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
    protected $table = 'printty_products_colors_sides';
}

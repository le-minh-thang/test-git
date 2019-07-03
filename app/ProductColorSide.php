<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductColorSide extends Model
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'master_item_type_sub_sides';

     protected $guarded = [];
}

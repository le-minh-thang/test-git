<?php

namespace App\PrinttyModels;

use Illuminate\Database\Eloquent\Model;

class ProductLinkedCode extends Model
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products_linked_codes';

    protected $connection = 'mysql_printty';
}

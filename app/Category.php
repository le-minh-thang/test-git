<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'categories';

    public function products()
    {
        return $this->hasMany(Product::class)->where('is_deleted', 0);
    }
}

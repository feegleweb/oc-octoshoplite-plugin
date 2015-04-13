<?php namespace Feegleweb\OctoshopLite\Models;

use Model;
use Carbon\Carbon;

/**
 * Product Model
 */
class ProdCat extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'feegleweb_octoshop_prod_cat';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];
}

<?php namespace Feegleweb\OctoshopLite\Models;

use Model;
use Carbon\Carbon;

/**
 * Product Model
 */
class Product extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'feegleweb_octoshop_products';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules
     */
    protected $rules = [
        'title' => ['required', 'between:4,255'],
        'slug' => [
            'required',
            'alpha_dash',
            'between:1,255',
            'unique:feegleweb_octoshop_products'
        ],
        'price' => ['numeric', 'max:99999999.99'],
    ];

    /**
     * @var array Attributes to mutate as dates
     */
    protected $dates = ['available_at', 'created_at', 'updated_at'];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'categories' => ['Feegleweb\OctoshopLite\Models\Category',
            'table' => 'feegleweb_octoshop_prod_cat',
            'order' => 'name',
        ],
    ];

    /**
     * @var array Image attachments
     */
    public $attachMany = [
        'images' => ['System\Models\File']
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeAvailable($query)
    {
        return $this->enabled();
    }

    /**
     * Allows filtering for specifc categories
     *
     * @param  Illuminate\Query\Builder  $query      QueryBuilder
     * @param  array                     $categories List of category ids
     * @return Illuminate\Query\Builder              QueryBuilder
     */
    public function scopeFilterCategories($query, $categories)
    {
        return $query->whereHas('categories', function($q) use ($categories) {
            $q->whereIn('id', $categories);
        });
    }

    public function inStock()
    {
        if (!$this->is_stockable) {
            return true;
        }

        return $this->stock > 0;
    }

    public function outOfStock()
    {
        return !$this->inStock();
    }

    public function getSquareThumb($size, $image)
    {
        return $image->getThumb($size, $size, ['mode' => 'crop']);
    }

    public function setUrl($pageName, $controller)
    {
        $params = [
            'id' => $this->id,
            'slug' => $this->slug,
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }
}

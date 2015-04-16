<?php namespace Feegleweb\OctoshopLite\Models;

use Model;

/**
 * Category Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\NestedTree;
    use \October\Rain\Database\Traits\Purgeable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'feegleweb_octoshop_categories';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'slug', 'description', 'is_enabled', 'is_visible'];

    /**
     * @var array Purgeable fields
     */
    protected $purgeable = ['title', 'is_subcategory'];

    /**
     * @var Relations
     */
    public $belongsToMany = [
        'products' => ['Feegleweb\OctoshopLite\Models\Product',
            'table' => 'feegleweb_octoshop_prod_cat',
            'order' => 'updated_at desc',
        ],
    ];
    public $belongsTo = [
        'parent' => ['Feegleweb\OctoshopLite\Models\Category', 'key' => 'parent_id'],
    ];

    /**
     * Image attachments
     *
     * @var array
     */
    public $attachOne = [
        'primary_image' => ['System\Models\File'],
        'secondary_image' => ['System\Models\File'],
    ];

    public function afterFetch()
    {
        // Set the dummy is_subcategory value for the backend switch
        // so it defaults to on when category has a parent_id set
        $this->is_subcategory = !!$this->parent_id;

        // Backwards compatibility for themes
        $this->title = $this->name;
    }

    public function beforeSave()
    {
        if (!$this->is_subcategory) {
            $this->parent_id = null;
        }
    }

    public function setUrl($pageName, $controller)
    {
        $params = [
            'id' => $this->id,
            'slug' => $this->slug,
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }

    public function scopeEnabled($q)
    {
        return $q->whereIsEnabled(true);
    }

    public function scopeVisible($q)
    {
        return $q->whereIsVisible(true);
    }

    public function scopeEnabledAndVisible($q)
    {
        return $q->enabled()->visible();
    }
}

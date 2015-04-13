<?php namespace Feegleweb\OctoshopLite\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Feegleweb\OctoshopLite\Models\Category as ShopCategory;
use Feegleweb\OctoshopLite\Models\Product as ShopProduct;

class Categories extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Shop Category List',
            'description' => 'Displays a list of shop categories on the page.',
        ];
    }

    public function defineProperties()
    {
        return [
            'categoryPage' => [
                'title'       => 'Category page',
                'description' => 'The name of the page to use when generating category links.',
                'type'        => 'dropdown',
                'default'     => 'shop/category',
                'group'       => 'Links',
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->prepareVars();
    }

    public function prepareVars()
    {
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
        $this->categories   = $this->page['categories']   = $this->listCategories();
    }

    public function listCategories()
    {
        return $this->fillExtraData(
            ShopCategory::enabledAndVisible()->with('products')->getNested(),
            $this->controller->pageUrl($this->categoryPage, false)
        );
    }

    public function fillExtraData($categories, $baseUrl, $rootCategory = true)
    {
        return $categories->each(function ($c) use ($baseUrl, $rootCategory) {
            $c->url = $baseUrl.($rootCategory ? '/' : '.').$c->slug;
            $c->productCount = count($c->products);

            if ($c->children) {
                $c->children = $this->fillExtraData($c->children, $c->url, false);
            }
        });
    }
}

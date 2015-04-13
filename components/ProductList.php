<?php namespace Feegleweb\OctoshopLite\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Feegleweb\OctoshopLite\Models\Category as ShopCategory;
use Feegleweb\OctoshopLite\Models\Product as ShopProduct;

class ProductList extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Shop Product List',
            'description' => 'Display products from a given category',
        ];
    }

    public function defineProperties()
    {
        return [
            'category' => [
                'title'       => 'Category',
                'description' => 'Category to filter the products by. Leave blank to show all products.',
                'type'        => 'string',
                'default'     => '{{ :slug }}'
            ],
            'basket' => [
                'title'       => 'Basket container element',
                'description' => 'CSS selector of the element to update when adding products to cart',
            ],
            'productPage' => [
                'title'       => 'Product Page',
                'description' => 'Name of the product page to use when generating links.',
                'type'        => 'dropdown',
                'default'     => 'shop/product',
                'group'       => 'Links',
            ],
        ];
    }

    public function getProductPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->prepareVars();

        $this->products = $this->page['products'] = $this->listProducts();
    }

    public function prepareVars()
    {
        $this->basket = $this->page['basket'] = $this->property('basket');
        $this->productPage = $this->page['productPage'] = $this->property('productPage');
        $this->category = $this->page['category'] = $this->loadCategory();
    }

    public function loadCategory()
    {
        if (!$slug = $this->property('category')) {
            return null;
        }

        if (strpos($slug, '.') !== false) {
            $slug_parts = explode('.', $slug);
            $slug = array_pop($slug_parts);
        }

        if (!$category = ShopCategory::whereSlug($slug)) {
            return null;
        }

        return $category->first();
    }

    public function listProducts()
    {
        $products = ShopProduct::available()->with(['images' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }]);

        if (!is_null($this->category)) {
            $products = $products->whereHas('categories', function ($q) {
                $q->where('category_id', '=', $this->category->id);
            });
        }

        $products = $products->get()->each(function ($product) {
            $product->setUrl($this->productPage, $this->controller);
        });

        return $products;
    }
}

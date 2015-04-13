<?php namespace Feegleweb\OctoshopLite;

use App;
use Backend;
use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;

/**
 * Shop Plugin Information File
 */
class Plugin extends PluginBase
{

    public function boot()
    {
        // Register service providers
        App::register('\Gloudemans\Shoppingcart\ShoppingcartServiceProvider');

        // Register facades
        $facade = AliasLoader::getInstance();
        $facade->alias('Cart', '\Gloudemans\Shoppingcart\Facades\Cart');
    }

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Octoshop Lite',
            'description' => 'Simplified version of Octoshop - the eCommerce plugin that lets you set up an online shop with ease.',
            'author'      => 'Dave Shoreman',
            'icon'        => 'icon-shopping-cart'
        ];
    }

    public function registerComponents()
    {
        return [
            'Feegleweb\OctoshopLite\Components\Basket' => 'shopBasket',
            'Feegleweb\OctoshopLite\Components\Categories' => 'shopCategories',
            'Feegleweb\OctoshopLite\Components\Product' => 'shopProduct',
            'Feegleweb\OctoshopLite\Components\ProductList' => 'shopProductList',
        ];
    }

    public function registerNavigation()
    {
        return [
            'shop' => [
                'label'       => 'Shop',
                'url'         => Backend::url('feegleweb/octoshoplite/products'),
                'icon'        => 'icon-shopping-cart',
                'permissions' => ['feegleweb.octoshop.*'],
                'order'       => 300,
            ],
        ];
    }

    public function registerPermissions()
    {
        return [
            'feegleweb.octoshop.access_products'   => ['label' => "Manage the shop's products"],
            'feegleweb.octoshop.access_categories' => ['label' => "Manage the shop categories"],
        ];
    }
}

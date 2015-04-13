<?php namespace Feegleweb\OctoshopLite\Components;

use Cart;
use Flash;
use Mail;
use Redirect;
use Session;
use Backend\Models\BrandSettings;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Cms\Classes\Settings;
use Feegleweb\OctoshopLite\Models\Order as ShopOrder;
use Feegleweb\OctoshopLite\Models\Product as ShopProduct;

class Basket extends ComponentBase
{
    public $items_removed = 0;

    public function componentDetails()
    {
        return [
            'name'        => "Basket Component",
            'description' => "Show the contents of and process the user's basket",
        ];
    }

    public function defineProperties()
    {
        return [
            'checkoutPage' => [
                'title'       => 'Checkout Page',
                'description' => 'Name of the page to redirect to when a user clicks Proceed to Checkout.',
                'type'        => 'dropdown',
                'default'     => 'checkout',
                'group'       => 'Links',
            ],
            'productPage' => [
                'title'       => 'Product Page',
                'description' => 'Name of the product page for the product titles.',
                'type'        => 'dropdown',
                'default'     => 'product',
                'group'       => 'Links',
            ],
            'basketComponent' => [
                'title'       => 'Basket Component',
                'description' => 'Component to use when adding products to basket',
                'default'     => 'shopBasket',
            ],
            'basketPartial' => [
                'title'       => 'Basket Partial',
                'description' => 'Partial to use when adding products to basket',
                'default'     => 'shopBasket::default',
            ],
            'recipientName' => [
                'title'       => 'Recipient Name',
                'description' => 'Name of the person to receive order confirmations',
                'group'       => 'Order confirmation email',
            ],
            'recipientEmail' => [
                'title'       => 'Recipient Email',
                'description' => 'Email address to receive order confirmation emails',
                'group'       => 'Order confirmation email',
            ],
        ];
    }

    public function getPaymentPageOptions()
    {
        return $this->getPagesDropdown();
    }

    public function getProductPageOptions()
    {
        return $this->getPagesDropdown();
    }

    public function getPagesDropdown()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->prepareVars();
    }

    public function prepareVars()
    {
        $this->registerBasketInfo();
        $this->registerPages();

        $this->setPageProp('basketComponent');
        $this->setPageProp('basketPartial');

        $this->recipientEmail = $this->property('recipientEmail');
        $this->recipientName = $this->property('recipientName');
    }

    public function registerBasketInfo()
    {
        $content = Cart::content();
        $content->each(function ($row) {
            $row->slug = $row->product->slug;
        });

        $this->setPageProp('basketItems', $content);
        $this->setPageProp('basketCount', Cart::count());
        $this->setPageProp('basketTotal', Cart::total());
    }

    public function registerPages()
    {
        $this->setPageProp('checkoutPage');
        $this->setPageProp('productPage');
    }

    protected function setPageProp($property, $val = null)
    {
        $val = $val ?: $this->property($property);

        $this->page[$property] = $val;
        $this->{$property} = $val;
    }

    public function onAddProduct()
    {
        $id = post('id');
        $quantity = post('quantity') ?: 1;

        $product = ShopProduct::find($id);

        Cart::associate('Product', 'Feegleweb\OctoshopLite\Models')->add(
            $id,
            $product->title,
            $quantity,
            $product->price
        );

        $this->registerBasketInfo();
    }

    public function onRemoveProduct()
    {
        Cart::remove(post('row_id'));

        $this->registerBasketInfo();

        return [
            'total' => $this->basketTotal ?: 0,
            'count' => $this->basketCount ?: 0,
        ];
    }

    public function onGoToCheckout()
    {
        if (!$this->stockCheck()) {
            return $this->redirectBackWithRemovedError();
        }

        return Redirect::to($this->checkoutPage);
    }

    public function onCheckout()
    {
        if (!$this->stockCheck()) {
            return $this->redirectBackWithRemovedError();
        }

        $content = Cart::content()->toArray();
        $total = Cart::total();

        $this->formatPrices($content, $total);

        Mail::sendTo(post('email'), 'feegleweb.octoshoplite::mail.orderconfirm', [
            'admin' => false,
            'name'  => post('first_name'),
            'site'  => BrandSettings::get('app_name'),
            'items' => $content,
            'total' => $total,
        ]);

        Mail::sendTo($this->recipientEmail, 'feegleweb.octoshoplite::mail.orderconfirm_admin', [
            'admin'   => true,
            'name'    => $this->recipientName,
            'address' => implode('<br>', [
                post('first_name').' '.post('last_name'),
                post('address'),
                post('town'),
                post('county'),
                post('postcode')
            ]),
            'site' => BrandSettings::get('app_name'),
            'items' => $content,
            'total' => $total,
        ]);

        return Redirect::to('/');
    }

    protected function formatPrices(&$items, &$total)
    {
        $formatter = new \NumberFormatter('en_GB', \NumberFormatter::CURRENCY);

        foreach ($items as $rowId => $item) {
            $items[$rowId]['price'] = $formatter->formatCurrency($item['price'], 'GBP');
        };

        $total = $formatter->formatCurrency($total, 'GBP');
    }

    protected function stockCheck()
    {
        $this->prepareVars();

        $content = Cart::content();

        if (!$this->processItems($content)) {
            return false;
        }

        return $content;
    }

    protected function processItems($items)
    {
        foreach ($items as $item) {
            $this->processItem($item);

            if ($this->items_removed > 0) {
                return false;
            }
        }

        return true;
    }

    protected function processItem($item)
    {
        // If the product doesn't exist, or it does exist but is out
        // of stock, we remove it from the cart and return early
        if (! ($p = ShopProduct::find($item->id))
            ||(isset($p) && !$p->inStock())
        ) {
            $this->removeCartRow($item->rowid);

            return;
        }

        if (!$p->is_stockable) {
            return;
        }

        $p->stock -= $item->qty;
        $p->save();
    }

    protected function redirectBackWithRemovedError()
    {
        $removed_many = $this->items_removed > 1;

        Flash::error(sprintf(
            "%d %s couldn't be found and %s removed automatically. Please checkout again.",
            $this->items_removed,
            ($removed_many ? 'items' : 'item'),
            ($removed_many ? 'were' : 'was')
        ));

        return Redirect::back();
    }

    protected function removeCartRow($rowId)
    {
        Cart::remove($rowId);

        $this->items_removed++;
    }
}

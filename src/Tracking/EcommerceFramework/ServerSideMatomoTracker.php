<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\EcommerceFramework;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICartProductActionAdd;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICartProductActionRemove;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICartUpdate;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICategoryPageView;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutComplete;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductView;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITracker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingItemBuilder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductAction;
use Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\Tracker;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServerSideMatomoTracker implements
    ITracker,
    IProductView,
    ICategoryPageView,
    ICartUpdate,
    ICartProductActionAdd,
    ICartProductActionRemove,
    ICheckoutComplete
{
    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * @var ITrackingItemBuilder
     */
    protected $trackingItemBuilder;

    /**
     * @var bool
     */
    private $handleCartAdd = true;

    /**
     * @var bool
     */
    private $handleCartRemove = true;

    /**
     * @var array
     */
    protected $assortmentTenants;

    /**
     * @var array
     */
    protected $checkoutTenants;

    public function __construct(Tracker $tracker, ITrackingItemBuilder $trackingItemBuilder, array $options = [], $assortmentTenants = [], $checkoutTenants = [])
    {
        $this->tracker = $tracker;
        $this->trackingItemBuilder = $trackingItemBuilder;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->processOptions($resolver->resolve($options));

        $this->assortmentTenants = $assortmentTenants;
        $this->checkoutTenants = $checkoutTenants;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // by default, a cart add/remove delegates to cart update
            // if you manually trigger cart update on every change you can
            // can set this to false to avoid handling of add/remove
            'handle_cart_add' => true,
            'handle_cart_remove' => true,
        ]);

        $resolver->setAllowedTypes('handle_cart_add', 'bool');
        $resolver->setAllowedTypes('handle_cart_remove', 'bool');
    }

    protected function processOptions(array $options)
    {
        $this->handleCartAdd = $options['handle_cart_add'];
        $this->handleCartRemove = $options['handle_cart_remove'];
    }

    /**
     * @inheritDoc
     */
    public function trackProductView(IProduct $product)
    {
        $item = $this->trackingItemBuilder->buildProductViewItem($product);

        $this->tracker->setEcommerceView(
            $item->getId(),
            trim($item->getName() . ' ' . $item->getVariant()),
            $this->filterCategories($item->getCategories()),
            $item->getPrice()
        );
    }

    /**
     * @inheritDoc
     */
    public function trackCategoryPageView($category, $page = null)
    {
        $category = $this->filterCategories($category);

        $this->tracker->setEcommerceView(
            null,
            null,
            $category
        );
    }

    /**
     * @inheritDoc
     */
    public function trackCartProductActionAdd(ICart $cart, IProduct $product, $quantity = 1)
    {
        if ($this->handleCartAdd) {
            $this->trackCartUpdate($cart);
        }
    }

    /**
     * @inheritDoc
     */
    public function trackCartProductActionRemove(ICart $cart, IProduct $product, $quantity = 1)
    {
        if ($this->handleCartRemove) {
            $this->trackCartUpdate($cart);
        }
    }

    /**
     * @inheritDoc
     */
    public function trackCartUpdate(ICart $cart)
    {
        $items = $this->trackingItemBuilder->buildCheckoutItemsByCart($cart);

        $this->doTrackEcommerceItems($items);

        $this->tracker->doTrackEcommerceCartUpdate(
            $cart->getPriceCalculator()->getGrandTotal()->getAmount()->asNumeric()
        );
    }

    /**
     * @inheritDoc
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        $items = $this->trackingItemBuilder->buildCheckoutItems($order);
        $transaction = $this->trackingItemBuilder->buildCheckoutTransaction($order);

        $calls = $this->doTrackEcommerceItems($items);

        $this->tracker->doTrackEcommerceOrder(
            $transaction->getId(),
            $transaction->getTotal(),
            $transaction->getSubTotal(),
            $transaction->getTax(),
            $transaction->getShipping()
        );
    }

    /**
     * @param ProductAction[] $items
     *
     * @throws
     */
    private function doTrackEcommerceItems(array $items)
    {
        foreach ($items as $item) {
            $this->tracker->addEcommerceItem(
                $item->getId(),
                trim($item->getName() . ' ' . $item->getVariant()),
                $item->getCategories(),
                $item->getPrice(),
                $item->getQuantity()
            );
        }
    }

    private function filterCategories($categories, int $limit = 5)
    {
        if (null === $categories) {
            return $categories;
        }

        $result = null;

        if (is_array($categories)) {
            // add max 5 categories
            $categories = array_slice($categories, 0, 5);

            $result = [];
            foreach ($categories as $category) {
                $category = trim((string)$category);
                if (!empty($category)) {
                    $result[] = $category;
                }
            }

            $result = array_slice($result, 0, $limit);
        } else {
            $result = trim((string)$categories);
        }

        if (!empty($result)) {
            return $result;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAssortmentTenants(): array
    {
        return $this->assortmentTenants;
    }

    /**
     * @inheritdoc
     */
    public function getCheckoutTenants(): array
    {
        return $this->checkoutTenants;
    }
}

<?php

/**
 * Class BitLoop_GoogleTagManager_Block_DataLayer_Category
 *
 * Category data for the dataLayer array
 *
 * @category BitLoop
 * @package  BitLoop_Core
 */
class BitLoop_GoogleTagManager_Block_DataLayer_Remarketing extends BitLoop_GoogleTagManager_Block_DataLayer_Abstract
{

    /**
     * The attributes to retrieve
     * @var array
     */
    protected $_entityAttributes = array(
    );

    /**
     * Hardcoded data for the product pages
     * @var array
     */
    protected $_customData = array(
    );

    public function _construct()
    {
        $this->_entityAttributes = $this->prepareEntityAttributes();
    }

    protected function prepareEntityAttributes()
    {
        $type = $this->_getPageType();
        $attributes = array();
        switch ($type)
        {
            //Homepage
            case 'home':
                $attributes = array(
                    'ecomm_pagetype' => array('callback' => '_getPageType'),
                );
                break;

            //Search
            case 'searchresults':
                $attributes = array(
                    'ecomm_pagetype' => array('callback' => '_getPageType'),
                );
                break;

            //Category view
            case 'category':
                $attributes = array(
                    'ecomm_pagetype' => array('callback' => '_getPageType'),
                    'ecomm_category' => 'name'
                );
                break;

            //Product view
            case 'product':
                $attributes = array(
                    'ecomm_prodid' => 'sku',
                    'ecomm_pagetype' => array('callback' => '_getPageType'),
                    'ecomm_totalvalue' => array('callback' => 'getProductPrice')
                );
                break;

            case 'cart':
                $attributes = array(
                    'ecomm_prodid' => array('callback' => '_getCartProductsSkus'),
                    'ecomm_pagetype' => array('callback' => '_getPageType'),
                    'ecomm_quantity' => array('callback' => '_getCartQtys'),
                    'ecomm_totalvalue' => array('callback' => '_getCartTotal'),
                );
                //Zend_Debug::dump($attributes);
                break;
            case 'purchase':
                $attributes = array(
                    'ecomm_prodid' => array('callback' => '_getTransactionProductsSkus'),
                    'ecomm_pagetype' => array('callback' => '_getPageType'),
                    'ecomm_quantity' => array('callback' => '_getTransactionProductsQtys'),
                    'ecomm_totalvalue' => 'grand_total'
                );
                break;
        }
        //Zend_Debug::dump($attributes);
        return $attributes;
    }

    /**
     * Get the current product instance
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _getEntityInstance()
    {
        if ($this->_getPageType() == 'product')
        {
            return Mage::registry('current_product');
        }
        if ($this->_getPageType() == 'category')
        {
            return Mage::registry('current_category');
        }
        if ($this->_getPageType() == 'cart')
        {
            //Zend_Debug::dump(Mage::getSingleton('checkout/session')->getQuote()); die;
            return Mage::getSingleton('checkout/session')->getQuote();
        }
        if ($this->_getPageType() == "success" || $this->_getPageType() == "purchase")
        {
            return Mage::getSingleton('checkout/session')->getLastRealOrder();
        }
        if ($this->_getPageType() == "searchresults" || $this->_getPageType() == "home" || $this->_getPageType() == '')
        {
            return new Varien_Object();
        }
    }

    /**
     * Get an array of ordered products - callback
     *
     * @return array
     */
    protected function _getTransactionProductsSkus()
    {
        $_orderItems = array();

        // If the order exists, loop through all products and add them
        if (($_order = $this->_getEntityInstance()) && $_order instanceof Mage_Sales_Model_Order && $_order->getId()
        )
        {
            /**
             * @var Mage_Sales_Model_Order_Item $_item
             */
            foreach ($_order->getAllItems() as $_item)
            {
                $_orderItems[] = $_item->getSku();
            }

            return $_orderItems;
        }

        return array();
    }

    protected function _getTransactionProductsQtys()
    {
        $_orderItems = array();

        // If the order exists, loop through all products and add them
        if (($_order = $this->_getEntityInstance()) && $_order instanceof Mage_Sales_Model_Order && $_order->getId()
        )
        {
            /**
             * @var Mage_Sales_Model_Order_Item $_item
             */
            foreach ($_order->getAllItems() as $_item)
            {
                $_orderItems[] = $_item->getQtyOrdered();
            }
            return $_orderItems;
        }

        return array();
    }

    /**
     * Get an array of category products
     *
     * @return array
     */
    protected function _getCategoryProductsCount()
    {
        if ($_category = $this->_getEntityInstance())
        {
            return $_category->getProductCount();
        }

        return array();
    }

    protected function _getPageType()
    {
        $action_name = Mage::app()->getFrontController()->getAction()->getFullActionName();
        //Zend_Debug::dump($action_name); die;
        if ($action_name == "catalog_product_view")
        {
            return 'product';
        }
        if ($action_name == "catalog_category_view")
        {
            return 'category';
        }
        if ($action_name == "cms_index_index")
        {
            return 'home';
        }
        if ($action_name == "catalogsearch_result_index")
        {
            return 'searchresults';
        }
        if ($action_name == "checkout_cart_index")
        {
            return 'cart';
        }
        if ($action_name == "checkouttester_index_success" || $action_name == "checkout_onepage_success")
        {
            return 'purchase';
        }
        
        
        return 'other';
    }

    protected function getProductPrice()
    {
        $product = $this->_getEntityInstance();
        if ($product instanceof Mage_Catalog_Model_Product)
        {
            return (float) number_format($product->getFinalPrice(), '2', '.', '');
        }
    }

    protected function _getCartProductsSkus($test)
    {
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $items = $cart->getAllVisibleItems();
        $skus = array();
        foreach ($items as $item)
        {
            $skus[] = $item->getSku();
        }
        return $skus;
    }

    protected function _getCartQtys()
    {
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $items = $cart->getAllVisibleItems();
        $qtys = array();
        foreach ($items as $item)
        {
        $qtys[] = $item->getQty();
        }
        return $qtys;
    }

    protected function _getCartTotal()
    {
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        if ($cart && $cart->getGrandTotal())
        {
            return $cart->getGrandTotal();
        }
        return 0;
    }

}

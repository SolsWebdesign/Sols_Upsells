<?php
/*
 * SolsWebdesign
 *
 * @category    Sols
 * @copyright   Copyright (c) 2017 SolsWebdesign.
 *
 */
require_once 'Mage/Checkout/controllers/CartController.php';
class Sols_Upsells_CartController extends Mage_Checkout_CartController
{

    public function addtoAction()
    {
        $cart       = $this->_getCart();
        $params     = $this->getRequest()->getParams();

        if (!$this->_validateFormKey()) {
            $this->_redirectReferer();
            return;
        }

        $product = Mage::getModel('catalog/product')->load($params['product']);

        try {
            if (isset($params['qty'])) {
                $filter         = new Zend_Filter_LocalizedToNormalized(array('locale' => Mage::app()->getLocale()->getLocaleCode()));
                $params['qty']  = $filter->filter($params['qty']);
            }
            $currentUrl         = Mage::helper('core/url')->getCurrentUrl();
            $params['uenc']     = Mage::helper('core/url')->urlEncode($currentUrl);
            $params['related_product'] = '';
            if (!$product) {
                $this->_goBack();
                return;
            }
            $cart->addProduct($product, $params);
            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
            if (!$cart->getQuote()->getHasError()){
                $message    = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                $this->_getSession()->addSuccess($message);
                $this->_redirectReferer();
            } else {
                $message = $this->__('There was an error and %s could not be added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                $this->_getSession()->addError($message);

                Mage::app()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                $this->_redirect('checkout/cart');
            }
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
            }
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
            Mage::logException($e);
            $this->_goBack();
        }
    }
}
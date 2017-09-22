<?php
/*
 * SolsWebdesign.nl
 * 
 * @category    Sols
 * @copyright   Copyright (c) 2017 Sols.
 *
 * Van Amasty heb je nodig de Mage::helper('amstockstatus')->getCustomStockStatusText($product)
 * 
 */
class Sols_Upsells_Helper_Data extends Mage_Core_Helper_Abstract
{
    // zet logging op true als je wilt zien wat hier gebeurt
    protected $logging      = false;
    public    $logfile;

    public function __construct()
    {
        $date               = date('Y-m-d');
        $this->logfile      = 'sols_upsells_helper_' . $date . '.log';
    }
    /**
     * Retrieve Configurable Attributes as array
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getMyConfigurableAttributesAsArray($product = null)
    {
        // we starten met de out of stock text:
        $outOfStock  = '(niet op voorraad)';
        // gebruik je nu Amasty dan uncomment de regel hier onder
        $outOfStock  = Mage::helper('amstockstatus')->getCustomStockStatusText($product)

        // hier haalt hij de attributes op voor de configurable
        $attributes  = Mage::getModel('catalog/product_type_configurable')->getConfigurableAttributes($product);
        // en de bijbehorende simples
        $simples    = Mage::getResourceModel('catalog/product_type_configurable_product_collection')
            ->setFlag('require_stock_items', true)
            ->setFlag('product_children', true)
            ->setProductFilter($product);

        if($this->logging) {
            Mage::log('We starten voor configurablbe met sku ' . $product->getSku(), null, $this->logfile);
        }

        // we bouwen hier een soort extra array op van het type
        //array(
        //    '135-25/134-22' => array('is_in_stock' => true, 'custom_status' => ''),
        //    '135-25/134-21' => array('is_in_stock' => false, 'custom_status' => 'Out of stock'),...
        // waarbij 135 de super_attribute is en de 25 de bijbehorende (internal) Magento value...
        // bovenstaande array is voor een product met 2 selects, eerst maat en dan lengte maat bijvoorbeeld

        $configStock = array();
        foreach($simples as $simpleProduct) {
            $key    = '';
            $i      = 0;
            foreach ($attributes as $attribute) {
                $simpleProduct->load($simpleProduct->getId());
                $value      = $simpleProduct->getData($attribute->getProductAttribute()->getAttributeCode());
                $superAttId = $attribute->getProductAttribute()->getId();
                if($this->logging) {
                    Mage::log('value ' . $value, null, $this->logfile);
                    Mage::log('super_attribute ' . $superAttId, null, $this->logfile);
                }
                if($i > 0 ){
                    $key .= '/'.$superAttId.'-'.$value;
                } else {
                    $key .= $superAttId.'-'.$value;
                }
                $i++;
            }
            if($simpleProduct->isSaleable()) {
                if($this->logging) {
                    Mage::log('product sku ' . $simpleProduct->getSku() . ' is in stock', null, $this->logfile);
                }
                // je zou dus eventueel ook een tekst voor in voorraad mee kunnen geven :-)
                $configStock[$key] = array('is_in_stock' => true, 'custom_status' => '');
            } else {
                if($this->logging) {
                    Mage::log('product sku ' . $simpleProduct->getSku() . ' OUT OF STOCK', null, $this->logfile);
                }
                $configStock[$key] = array('is_in_stock' => false, 'custom_status' => $outOfStock);
            }
        }

        // dit hier is eigenlijk het standaard ding met configatts dat je nodig hebt voor je configurable
        $confArr = Mage::getModel('catalog/product_type_configurable')->getConfigurableAttributesAsArray($product);
        // geef het standaard ding samen met de config stock naar voren en doe daar wat jQuery magic
        return array('config_atts' => $confArr, 'config_stock' => $configStock);
    }
}
<?php

use AutomaterSDK\Response\Entity\Product;

class KutybaIt_AutomaterPl_Model_Attribute_Source_Product extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [];
            $this->_options[] = [
                'label' => Mage::helper('adminhtml')->__('-- Please Select --'),
                'value' => '',
            ];
        }

        if (!Mage::helper("automaterpl")->isActive()) {
            return $this->_options;
        }

        $automater = Mage::getModel('automaterpl/automater_proxy');
        $products = $automater->getAllProducts();

        $this->_transformProductsToOptions($products);

        return $this->_options;
    }

    /**
     * @param Product[] $products
     */
    private function _transformProductsToOptions($products)
    {
        foreach ($products as $product) {
            $this->_options[] = [
                'label' => $product->getName(),
                'value' => $product->getId(),
            ];
        }
    }
}

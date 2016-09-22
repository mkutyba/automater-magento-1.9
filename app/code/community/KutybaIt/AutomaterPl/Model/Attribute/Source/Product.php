<?php

class KutybaIt_AutomaterPl_Model_Attribute_Source_Product extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options)) {
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

    private function _transformProductsToOptions($products)
    {
        if (is_array($products)) {
            foreach ($products as $product) {
                $this->_options[] = [
                    'label' => $product['name'],
                    'value' => $product['id'],
                ];
            }
        }
    }
}
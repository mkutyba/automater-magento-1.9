<?php

require_once(Mage::getBaseDir('lib') . '/Automater-PHP-SDK/autoload.php');

use \Automater\Automater;

class KutybaIt_AutomaterPl_Model_Automater_Proxy extends Mage_Core_Model_Abstract
{
    private $_instance = null;

    private function _getInstance()
    {
        if (is_null($this->_instance)) {
            $this->_instance = new Automater(Mage::helper("automaterpl")->getApiKey(), Mage::helper("automaterpl")->getApiSecret());
        }

        return $this->_instance;
    }

    public function getCountForProduct($productId)
    {
        return $this->_getInstance()->getAvailableProductsCount($productId);
    }

    public function createTransaction($products, $email, $phone, $label)
    {
        $listingIds = array_keys($products);
        $quantity = array_values($products);
        return $this->_getInstance()->createTransaction($listingIds, $email, $quantity, $phone, 'pl', 1, $label);
    }

    public function createPayment($cartId, $paymentId, $amount)
    {
        return $this->_getInstance()->createPayment('cart', $cartId, $paymentId, $amount, "PLN");
    }

    public function getAllProducts()
    {
        $data = [];
        $result = $this->_getInstance()->getProducts();
        if ($result['code'] == '200') {
            $data = $result['data'];
            $result['data']['count'] = 100;
            $count = $result['count'];

            if ($count > 50) {
                for ($i = 1; $i * 50 < $count; $i++) {
                    $result = $this->_getInstance()->getProducts($i + 1);
                    if ($result['code'] == '200') {
                        $data = array_merge($data, $result['data']);
                    }
                }
            }
        }

        return $data;
    }
}

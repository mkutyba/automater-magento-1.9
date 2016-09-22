<?php

class KutybaIt_AutomaterPl_Model_Observer
{
    private $_automater;

    public function createTransaction(Varien_Event_Observer $observer)
    {
        if (!Mage::helper("automaterpl")->isActive()) {
            return;
        }

        /** @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();

        $items = $order->getAllVisibleItems();
        $result = [];
        $result[] = Mage::helper("automaterpl")->__("Automater.pl codes:");

        try {
            $this->_automater = Mage::getModel('automaterpl/automater_proxy');
        } catch (Exception $e) {
            $result[] = $e->getMessage();
            $this->_setOrderStatus($result, $order);
            return;
        }

        $products = $this->_validateItems($items, $result);

        $this->_validateProductsStock($products, $result);

        $this->_createAutomaterTransaction($products, $order, $result);

        $this->_setOrderStatus($result, $order);
    }

    private function _setOrderStatus($status, $order)
    {
        $order->addStatusHistoryComment(implode("<br>", $status));
        $order->save();
    }

    private function _validateItems($items, &$result)
    {
        $products = [];

        foreach ($items as $item) {
            try {
                $automaterProductId = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->getSku())->getAutomaterProductId();
                if (!$automaterProductId) {
                    $result[] = Mage::helper("automaterpl")->__("No codes for product: %s [%s]", $item->getName(), $item->getSku());
                    continue;
                }

                $qty = intval($item->getQtyOrdered());
                if (is_nan($qty) || $qty <= 0) {
                    $result[] = Mage::helper("automaterpl")->__("Invalid quantity of product: %s [%s]", $item->getName(), $item->getSku());
                    continue;
                }

                if (!isset($products[$automaterProductId])) {
                    $products[$automaterProductId] = 0;
                }
                $products[$automaterProductId] = $products[$automaterProductId] + $qty;
            } catch (Exception $e) {
                $result[] = $e->getMessage() . Mage::helper("automaterpl")->__(": %s [%s]", $item->getName(), $item->getSku());
            }
        }

        return $products;
    }

    private function _validateProductsStock(&$products, &$result)
    {
        foreach ($products as $automaterProductId => $qty) {
            try {
                if (!$qty) {
                    $result[] = Mage::helper("automaterpl")->__("No codes for ID: %s", $automaterProductId);
                    unset($products[$automaterProductId]);
                    continue;
                }
                $codesCount = $this->_automater->getCountForProduct($automaterProductId);
                if (!$codesCount) {
                    $result[] = Mage::helper("automaterpl")->__("No codes for ID: %s", $automaterProductId);
                    unset($products[$automaterProductId]);
                    continue;
                }
                if ($codesCount < $qty) {
                    $result[] = Mage::helper("automaterpl")->__("Not enough codes for ID, sent less: %s", $automaterProductId);
                    $products[$automaterProductId] = $codesCount;
                }
            } catch (Exception $e) {
                $result[] = $e->getMessage() . Mage::helper("automaterpl")->__(": %s", $automaterProductId);
                unset($products[$automaterProductId]);
            }
        }
    }

    private function _createAutomaterTransaction($products, $order, &$result)
    {
        if (sizeof($products)) {
            try {
                $response = $this->_automater->createTransaction(
                    $products, $order->getCustomerEmail(),
                    $order->getBillingAddress()->getTelephone(),
                    Mage::helper("automaterpl")->__("Order from %s, id: #%s", Mage::app()->getStore()->getBaseUrl(), $order->getIncrementId())
                );
                if ($response['code'] == '200') {
                    if ($automaterCartId = $response['cart_id']) {
                        $order->setAutomaterplCartId($automaterCartId);
                        $order->save();
                        $result[] = Mage::helper("automaterpl")->__("Created cart number: %s", $automaterCartId);
                    }
                }
            } catch (Exception $e) {
                $result[] = $e->getMessage();
            }
        }
    }

    public function payTransaction(Varien_Event_Observer $observer)
    {
        if (!Mage::helper("automaterpl")->isActive()) {
            return;
        }

        /** @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        $result = [];
        $result[] = Mage::helper("automaterpl")->__("Automater.pl payment:");

        $automaterCartId = $order->getAutomaterplCartId();
        if (!$automaterCartId) {
            return;
        }

        try {
            $this->_automater = Mage::getModel('automaterpl/automater_proxy');
            $response = $this->_automater->createPayment($automaterCartId, $order->getIncrementId(), $order->getSubtotalInclTax());
            if ($response['code'] == '200') {
                $result[] = Mage::helper("automaterpl")->__("Paid successfully: %s", $automaterCartId);
            }
        } catch (Exception $e) {
            $result[] = $e->getMessage();
            $this->_setOrderStatus($result, $order);
            return;
        }

        $this->_setOrderStatus($result, $order);
    }
}
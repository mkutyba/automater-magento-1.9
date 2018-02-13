<?php

require_once Mage::getBaseDir('lib') . '/kutybait-AutomaterPl/autoload.php';

use AutomaterSDK\Exception\ApiException;
use AutomaterSDK\Exception\NotFoundException;
use AutomaterSDK\Exception\TooManyRequestsException;
use AutomaterSDK\Exception\UnauthorizedException;
use AutomaterSDK\Response\PaymentResponse;
use AutomaterSDK\Response\TransactionResponse;

class KutybaIt_AutomaterPl_Model_Observer
{
    private $_automater;

    /**
     * @param Varien_Event_Observer $observer
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function createTransaction($observer)
    {
        if (!Mage::helper('automaterpl')->isActive()) {
            return;
        }

        /** @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();

        $items = $order->getAllVisibleItems();
        $result = [];
        $result[] = Mage::helper('automaterpl')->__('Automater.pl codes:');

        $products = $this->_validateItems($items, $result);

        $this->_createAutomaterTransaction($products, $order, $result);

        $this->_setOrderStatus($result, $order);
    }

    /**
     * @param string[] $status
     * @param Mage_Sales_Model_Order $order
     * @throws Exception
     */
    private function _setOrderStatus($status, $order)
    {
        $order->addStatusHistoryComment(implode('<br>', $status));
        $order->save();
    }

    /**
     * @param Mage_Sales_Model_Order_Item[] $items
     * @param string[] $result
     * @return array
     */
    private function _validateItems($items, &$result)
    {
        $products = [];

        foreach ($items as $item) {
            try {
                $automaterProductId = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->getSku())->getAutomaterProductId();
                if (!$automaterProductId) {
                    $result[] = Mage::helper('automaterpl')->__('Product not managed by automater: %s [%s]', $item->getName(), $item->getSku());
                    continue;
                }

                $qty = (int)$item->getQtyOrdered();
                if (is_nan($qty) || $qty <= 0) {
                    $result[] = Mage::helper('automaterpl')->__('Invalid quantity of product: %s [%s]', $item->getName(), $item->getSku());
                    continue;
                }

                if (!isset($products[$automaterProductId])) {
                    $products[$automaterProductId]['qty'] = 0;
                    $products[$automaterProductId]['price'] = $item->getPrice();
                    $products[$automaterProductId]['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                }
                $products[$automaterProductId]['qty'] += $qty;
            } catch (Exception $e) {
                $result[] = $e->getMessage() . Mage::helper("automaterpl")->__(": %s [%s]", $item->getName(), $item->getSku());
            }
        }

        return $products;
    }

    /**
     * @param array $products
     * @param Mage_Sales_Model_Order $order
     * @param string[] $result
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    private function _createAutomaterTransaction($products, $order, &$result)
    {
        if (count($products)) {
            $email = $order->getBillingAddress()->getEmail();
            $phone = $order->getBillingAddress()->getTelephone();
            $label = Mage::helper('automaterpl')->__('Order from %s, id: #%s', Mage::app()->getStore()->getBaseUrl(), $order->getIncrementId());

            try {
                /** @var TransactionResponse $response */
                $this->_automater = Mage::getModel('automaterpl/automater_proxy');
                $response = $this->_automater->createTransaction($products, $email, $phone, $label);
                if ($response && $automaterCartId = $response->getCartId()) {
                    $order->setAutomaterplCartId($automaterCartId);
                    $order->save();
                    $result[] = Mage::helper('automaterpl')->__('Created cart number: %s', $automaterCartId);
                }
            } catch (UnauthorizedException $exception) {
                $this->handleException($result, 'Invalid API key');
            } catch (TooManyRequestsException $e) {
                $this->handleException($result, 'Too many requests to Automater: ' . $e->getMessage());
            } catch (NotFoundException $e) {
                $this->handleException($result, 'Not found - invalid params');
            } catch (ApiException $e) {
                $this->handleException($result, $e->getMessage());
            } catch (Exception $e) {
                $this->handleException($result, $e->getMessage());
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function payTransaction($observer)
    {
        if (!Mage::helper('automaterpl')->isActive()) {
            return;
        }

        /** @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        $result = [];
        $result[] = Mage::helper('automaterpl')->__('Automater.pl payment:');

        $automaterCartId = $order->getAutomaterplCartId();
        if (!$automaterCartId) {
            return;
        }

        $paymentId = $order->getIncrementId();
        $amount = $order->getSubtotal();
        $description = $order->getPayment()->getMethodInstance()->getTitle();

        try {
            /** @var PaymentResponse $response */
            $this->_automater = Mage::getModel('automaterpl/automater_proxy');
            $response = $this->_automater->createPayment($automaterCartId, $paymentId, $amount, $description);
            if ($response) {
                $result[] = Mage::helper('automaterpl')->__('Paid successfully: %s', $automaterCartId);
            }
        } catch (UnauthorizedException $exception) {
            $this->handleException($result, 'Invalid API key');
        } catch (TooManyRequestsException $e) {
            $this->handleException($result, 'Too many requests to Automater: ' . $e->getMessage());
        } catch (NotFoundException $e) {
            $this->handleException($result, 'Not found - invalid params');
        } catch (ApiException $e) {
            $this->handleException($result, $e->getMessage());
        } catch (Mage_Core_Model_Store_Exception $e) {
            $this->handleException($result, $e->getMessage());
        }

        $this->_setOrderStatus($result, $order);
    }

    /**
     * @param array $result
     * @param string $exceptionMessage
     */
    protected function handleException(array &$result, $exceptionMessage)
    {
        Mage::logException(new Exception('Automater.pl: ' . $exceptionMessage));
        $result[] = 'Automater.pl: ' . $exceptionMessage;
    }
}

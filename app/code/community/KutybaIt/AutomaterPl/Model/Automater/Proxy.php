<?php

require_once Mage::getBaseDir('lib') . '/kutybait-AutomaterPl/autoload.php';

use AutomaterSDK\Client\Client;
use AutomaterSDK\Exception\ApiException;
use AutomaterSDK\Exception\NotFoundException;
use AutomaterSDK\Exception\TooManyRequestsException;
use AutomaterSDK\Exception\UnauthorizedException;
use AutomaterSDK\Request\Entity\TransactionProduct;
use AutomaterSDK\Request\PaymentRequest;
use AutomaterSDK\Request\ProductsRequest;
use AutomaterSDK\Request\TransactionRequest;
use AutomaterSDK\Response\Entity\Product;
use AutomaterSDK\Response\PaymentResponse;
use AutomaterSDK\Response\ProductsResponse;
use AutomaterSDK\Response\TransactionResponse;

class KutybaIt_AutomaterPl_Model_Automater_Proxy extends Mage_Core_Model_Abstract
{
    private $_instance;

    /**
     * @return Client
     */
    private function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Client(Mage::helper('automaterpl')->getApiKey(), Mage::helper('automaterpl')->getApiSecret());
        }

        return $this->_instance;
    }

    /**
     * @param string $productId
     * @return int
     */
    public function getCountForProduct($productId)
    {
        try {
            $product = $this->_getInstance()->getProductDetails($productId);

            return (int)$product->getAvailableCodes();
        } catch (UnauthorizedException $exception) {
            Mage::logException(new Exception('Automater.pl: Invalid API key'));
        } catch (TooManyRequestsException $exception) {
            Mage::logException(new Exception('Automater.pl: Too many requests to Automater: ' . $exception->getMessage()));
        } catch (NotFoundException $exception) {
            Mage::logException(new Exception('Automater.pl: Not found - invalid params'));
        } catch (ApiException $exception) {
            Mage::logException(new Exception('Automater.pl: ' . $exception->getMessage()));
        }

        return 0;
    }

    /**
     * @param array $products
     * @param string $email
     * @param string $phone
     * @param string $label
     * @return TransactionResponse
     * @throws ApiException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    public function createTransaction($products, $email, $phone, $label)
    {
        $transactionRequest = new TransactionRequest();
        switch (strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2))) {
            case 'pl':
                $transactionRequest->setLanguage(TransactionRequest::LANGUAGE_PL);
                break;
            case 'en':
                $transactionRequest->setLanguage(TransactionRequest::LANGUAGE_EN);
                break;
            default:
                $transactionRequest->setLanguage(TransactionRequest::LANGUAGE_EN);
                break;
        }
        if ($email) {
            $transactionRequest->setEmail($email);
            $transactionRequest->setSendStatusEmail(TransactionRequest::SEND_STATUS_EMAIL_TRUE);
        }
        $transactionRequest->setPhone($phone);
        $transactionRequest->setCustom($label);

        $transactionProducts = [];
        foreach ($products as $product_id => $product) {
            $transactionProduct = new TransactionProduct();
            $transactionProduct->setId($product_id);
            $transactionProduct->setQuantity($product['qty']);
            $transactionProduct->setPrice($product['price']);
            $transactionProduct->setCurrency($product['currency']);
            $transactionProducts[] = $transactionProduct;
        }
        $transactionRequest->setProducts($transactionProducts);

        return $this->_getInstance()->createTransaction($transactionRequest);
    }

    /**
     * @param string $cartId
     * @param string $paymentId
     * @param string $amount
     * @param string $description
     * @return PaymentResponse
     * @throws ApiException
     * @throws Mage_Core_Model_Store_Exception
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    public function createPayment($cartId, $paymentId, $amount, $description)
    {
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPaymentId($paymentId);
        $paymentRequest->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
        $paymentRequest->setAmount($amount);
        $paymentRequest->setDescription($description);

        return $this->_getInstance()->postPayment($cartId, $paymentRequest);
    }

    /**
     * @return Product[]
     */
    public function getAllProducts()
    {
        $productsResponse = $this->getProducts(1);
        $data = $productsResponse->getData()->toArray();

        for ($page = 2; $page <= $productsResponse->getPagesCount(); $page++) {
            $productsResponse = $this->getProducts($page);
            if ($productsResponse) {
                $data = array_merge($data, $productsResponse->getData()->toArray());
            }
        }

        return $data;
    }

    /**
     * @param string $page
     * @return ProductsResponse|bool
     */
    protected function getProducts($page)
    {
        $client = $this->_getInstance();

        $productRequest = new ProductsRequest();
        $productRequest->setType(ProductsRequest::TYPE_SHOP);
        $productRequest->setStatus(ProductsRequest::STATUS_ACTIVE);
        $productRequest->setPage($page);
        $productRequest->setLimit(100);

        try {
            return $client->getProducts($productRequest);
        } catch (UnauthorizedException $exception) {
            Mage::logException(new Exception('Automater.pl: Invalid API key'));
        } catch (TooManyRequestsException $exception) {
            Mage::logException(new Exception('Automater.pl: Too many requests to Automater: ' . $exception->getMessage()));
        } catch (NotFoundException $exception) {
            Mage::logException(new Exception('Automater.pl: Not found - invalid params'));
        } catch (ApiException $exception) {
            Mage::logException(new Exception('Automater.pl: ' . $exception->getMessage()));
        }

        return false;
    }
}

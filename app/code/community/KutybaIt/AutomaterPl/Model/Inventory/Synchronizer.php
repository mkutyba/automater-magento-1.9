<?php

class KutybaIt_AutomaterPl_Model_Inventory_Synchronizer
{
    /**
     * @throws Exception
     */
    public function synchronizeInventory()
    {
        if (!Mage::helper("automaterpl")->isActive()) {
            return;
        }

        $collection = $this->_prepareProductsCollection();
        $automaterStocks = $this->_fetchAutomaterStocks();

        foreach ($collection as $product) {
            if ($automaterProductId = $product->getAutomaterProductId()) {
                $qty = $automaterStocks[$automaterProductId];
                if ($qty === null) {
                    continue;
                }
                $this->_updateProductStock($product->getId(), $qty);
            }
        }
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    private function _prepareProductsCollection()
    {
        $collection = Mage::getModel("catalog/product")->getCollection()
            ->addAttributeToFilter("automater_product_id", ["notnull" => true]);
        return $collection;
    }

    /**
     * @return array
     */
    private function _fetchAutomaterStocks()
    {
        $automater = Mage::getModel('automaterpl/automater_proxy');

        $automaterProducts = $automater->getAllProducts();
        $automaterStocks = [];
        foreach ($automaterProducts as $product) {
            $automaterStocks[$product->getId()] = $product->getAvailableCodes();
        }

        return $automaterStocks;
    }

    /**
     * @param string $productId
     * @param string $qty
     * @throws Exception
     */
    private function _updateProductStock($productId, $qty)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        if ($stockItem->getId() && $stockItem->getManageStock()) {
            $stockItem->setQty($qty);
            $stockItem->setIsInStock((int)($qty > 0));
            $stockItem->save();
        }
    }
}

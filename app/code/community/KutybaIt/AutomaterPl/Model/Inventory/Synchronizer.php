<?php

class KutybaIt_AutomaterPl_Model_Inventory_Synchronizer
{
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
                if (!isset($qty)) {
                    continue;
                }
                $this->_updateProductStock($product->getId(), $qty);
            }
        }
    }

    private function _prepareProductsCollection()
    {
        $collection = Mage::getModel("catalog/product")->getCollection()
            ->addAttributeToFilter("automater_product_id", ["notnull" => true]);
        return $collection;
    }

    private function _fetchAutomaterStocks()
    {
        $automater = Mage::getModel('automaterpl/automater_proxy');

        $automaterProducts = $automater->getAllProducts();
        $automaterStocks = [];
        if (is_array($automaterProducts)) {
            $automaterStocks = array_combine(array_column($automaterProducts, "id"), array_column($automaterProducts, "available"));
        }

        return $automaterStocks;
    }

    private function _updateProductStock($productId, $qty)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        if ($stockItem->getId() and $stockItem->getManageStock()) {
            $stockItem->setQty($qty);
            $stockItem->setIsInStock((int)($qty > 0));
            $stockItem->save();
        }
    }
}
<?php

class KutybaIt_AutomaterPl_Model_System_Config_Backend_Inventory_Cron_Interval extends Mage_Core_Model_Config_Data
{
    const CRON_PATH = "crontab/jobs/automaterpl_synchronize_inventory/schedule/cron_expr";

    protected function _afterSave()
    {
        $active = $this->getData('groups/inventory_synchronization/fields/active/value');
        $interval = $this->getData('groups/inventory_synchronization/fields/interval/value');

        if ($active && $interval > 0) {
            $cronExpression = "*/$interval * * * *";
        } else {
            $cronExpression = null;
        }

        try {
            Mage::getModel('core/config_data')
                ->load(self::CRON_PATH, 'path')
                ->setValue($cronExpression)
                ->setPath(self::CRON_PATH)
                ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }
    }
}
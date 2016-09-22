<?php

class KutybaIt_AutomaterPl_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ACTIVE = 'automaterpl/api_configuration/active';
    const XML_PATH_API_KEY = 'automaterpl/api_configuration/api_key';
    const XML_PATH_API_SECRET = 'automaterpl/api_configuration/api_secret';

    public function isActive()
    {
        return Mage::getStoreConfig(self::XML_PATH_ACTIVE);
    }

    public function getApiKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_API_KEY);
    }

    public function getApiSecret()
    {
        return Mage::getStoreConfig(self::XML_PATH_API_SECRET);
    }
}
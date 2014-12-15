<?php

/**
 * Class HeyStaks_Core_Helper_Data
 */
class HeyStaks_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) Mage::getStoreConfig('heystaks/general/active');
    }

    /**
     * @return bool
     */
    public function isTest()
    {
        return (bool) Mage::getStoreConfig('heystaks/general/test');
    }

    /**
     * @param $entityType
     * @return bool
     */
    public function canIncludeInSearch($entityType)
    {
        return (bool) Mage::getStoreConfig('heystaks/page_types/' . $entityType);
    }

    /**
     * @param $data
     */
    public function log($data, $type = 'default')
    {
        if (Mage::getStoreConfig('heystaks/general/log')) {
            Mage::log($data, null, 'heystaks/' . $type . '.log', true);
        }
    }

    /**
     * @return string
     */
    public function getStoreLanguage()
    {
        return substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2);
    }

    /**
     * @return bool
     */
    public function areCookiesEnabled()
    {
        $cookies = Mage::getSingleton('core/cookie')->get();
        return !empty($cookies);
    }
}
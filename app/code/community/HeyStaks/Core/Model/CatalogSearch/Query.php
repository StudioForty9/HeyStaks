<?php

/**
 * Class HeyStaks_Core_Model_CatalogSearch_Query
 */
class HeyStaks_Core_Model_CatalogSearch_Query extends Mage_CatalogSearch_Model_Query
{
    /**
     * @return Mage_CatalogSearch_Model_Resource_Query_Collection|mixed
     */
    public function getSuggestCollection()
    {
        $collection = $this->getData('suggest_collection');
        if (is_null($collection)) {
            $heystaks = Mage::getModel('heystaks/heystaks');
            $results = $heystaks->getResults();
            $this->setData('suggest_collection', $results);
        }

        return $this->getData('suggest_collection');
    }
}
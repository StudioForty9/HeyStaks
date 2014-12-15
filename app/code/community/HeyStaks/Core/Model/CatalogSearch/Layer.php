<?php

/**
 * Class HeyStaks_Core_Model_Catalog_Layer
 */
class HeyStaks_Core_Model_CatalogSearch_Layer extends Mage_CatalogSearch_Model_Layer
{

    public function getProductCollection()
    {
        return Mage::registry('heystaks_collection') ? Mage::registry('heystaks_collection') :
            parent::getProductCollection();
    }

    protected function _getSetIds()
    {
        return Mage::getResourceModel('eav/entity_attribute_set_collection')->getAllIds();
    }

    public function getFilterableAttributes()
    {
        $setIds = $this->_getSetIds();
        if (!$setIds) {
            return array();
        }
        /** @var $collection Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection');
        $collection
            ->setItemObjectClass('catalog/resource_eav_attribute')
            ->setAttributeSetFilter($setIds)
            ->addStoreLabel(Mage::app()->getStore()->getId())
            ->setOrder('position', 'ASC');
        $collection = $this->_prepareAttributeCollection($collection);
        $collection->load();

        return $collection;
    }

}

<?php

/**
 * Class HeyStaks_Core_Model_CatalogSearch_Indexer
 */
class HeyStaks_Core_Model_CatalogSearch_Indexer extends Mage_CatalogSearch_Model_Indexer_Fulltext
{
    /**
     * @var $_heystaks Heystaks_Core_Model_Heystaks
     */
    protected $_heystaks = null;

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_heystaks = Mage::getModel('heystaks/heystaks');
    }

    /**
     *
     */
    public function reindexAll()
    {
        if (!Mage::helper('heystaks')->isEnabled()) {
            return parent::reindexAll();
        }

        if (Mage::helper('heystaks')->isTest()) {
            parent::reindexAll();
        }

        $appEmulation = Mage::getSingleton('core/app_emulation');

        $configs = Mage::getModel('core/resource_config_data_collection');
        $configs->addFieldToFilter('path', 'heystaks/authentication/application_id');

        foreach($configs as $config)
        {
            switch($config->getScope())
            {
                case 'default':
                    $storeId = Mage::app()->getDefaultStoreView()->getId();
                    break;
                case 'website':
                    $website = Mage::getModel('core/website')->load($config->getScopeId());
                    $storeId = $website->getDefaultStore()->getId();
                    break;
                case 'store':
                default:
                    $storeId = $config->getScopeId();
            }
        }

        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        $this->_heystaks->fetchToken();
        $this->_reindexProducts();
        //$this->_reindexCategories();
        //$this->_reindexPages();

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
    }

    /**
     *
     */
    protected function _reindexProducts()
    {
        if (Mage::helper('heystaks')->canIncludeInSearch('product')) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(
                    array(
                        'sku', 'name', 'description', 'short_description',
                        'image', 'price', 'manufacturer', 'color'
                    )
                )
                ->addStoreFilter(1)
                ->addUrlRewrite()
                ->setPageSize(100)
                ->setCurPage(1);

            if (!Mage::getStoreConfig('heystaks/frontend/include_out_of_stock')) {
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
            }

            Mage::helper('heystaks')->log(
                'Total number of Products to be indexed:' . $collection->getSize(),
                'indexing'
            );

            $i = 1;
            while ($i <= $collection->getLastPageNumber()) {
                $this->_heystaks->indexProducts($collection);
                $i++;
                $collection->clear();
                $collection->setCurPage($i)->load();
            }
        }
    }

    /**
     *
     */
    protected function _reindexCategories()
    {
        if (Mage::helper('heystaks')->canIncludeInSearch('category')) {
            $collection = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect(array('name', 'description', 'image', 'url_key'))
                ->addAttributeToFilter('is_active', 1)
                ->setPageSize(100)
                ->setCurPage(1);

            $i = 1;
            while ($i <= $collection->getLastPageNumber()) {
                $this->_heystaks->indexCategories($collection);
                $i++;
                $collection->setCurPage($i)->load();
            }
        }
    }

    /**
     *
     */
    protected function _reindexPages()
    {
        if (Mage::helper('heystaks')->canIncludeInSearch('page')) {
            $collection = Mage::getModel('cms/page')->getCollection()
                ->addFieldToFilter('is_active', 1)
                ->setPageSize(100)
                ->setCurPage(1);

            $i = 1;
            while ($i <= $collection->getLastPageNumber()) {
                $this->_heystaks->indexPages($collection);
                $i++;
                $collection->setCurPage($i)->load();
            }
        }
    }
}
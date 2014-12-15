<?php

/**
 * Class HeyStaks_Core_Block_CatalogSearch_Autocomplete
 */
class HeyStaks_Core_Block_CatalogSearch_Autocomplete extends HeyStaks_Core_Block_CatalogSearch_Autocomplete_Abstract
{
    /**
     *
     */
    public function __construct()
    {
        $this->_template = 'heystaks/autocomplete.phtml';
    }

    /**
     * @return array|mixed
     */
    public function getSuggestData()
    {
        if (!$this->_suggestData) {
            $collection = $this->helper('catalogsearch')->getSuggestCollection();

            $counter = 0;
            $data = array();
            foreach ($collection as $item) {
                $_data = array(
                    'title' => $item->getName(),
                    'sku' => $item->getSku(),
                    'row_class' => (++$counter)%2?'odd':'even',
                    'num_of_results' => 1, //$item->getNumResults()
                    'url' => $item->getProductUrl(),
                    'description' => Mage::helper('core/string')->truncate(
                            $item->getShortDescription(),
                            150
                        ),
                    'image' => Mage::helper('catalog/image')->init($item, 'small_image')->resize(150, 150),
                    'price' => $item->getFinalPrice()
                );

                $data[$counter] = $_data;
                $counter++;
            }
            $this->_suggestData = $data;
        }
        return $this->_suggestData;
    }

    public function showSku()
    {
        return Mage::getStoreConfig('heystaks/frontend/show_sku');
    }
}
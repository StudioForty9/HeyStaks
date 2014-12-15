<?php

if (Mage::getStoreConfig('heystaks/general/use_autocomplete')) {
class HeyStaks_Core_Block_CatalogSearch_Autocomplete_Abstract extends Mage_Core_Block_Template { }
} else {
class HeyStaks_Core_Block_CatalogSearch_Autocomplete_Abstract extends Mage_CatalogSearch_Block_Autocomplete { }
}
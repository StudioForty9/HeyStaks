<?php

require_once 'Mage/CatalogSearch/controllers/AdvancedController.php';

/**
 * Class HeyStaks_Core_AdvancedController
 */
class HeyStaks_Core_AdvancedController extends Mage_CatalogSearch_AdvancedController
{

    /**
     *
     */
    public function indexAction()
    {
        if (!Mage::helper('heystaks')->isEnabled()) {
            return parent::indexAction();
        }

        Mage::getSingleton('core/session')->addError(
            $this->__('Advanced Search is not currently available. Please use the standard search')
        );

        $this->_redirect('*/result/');
    }
}
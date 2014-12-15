<?php

require_once 'Mage/CatalogSearch/controllers/ResultController.php';

/**
 * Class HeyStaks_Core_ResultController
 */
class HeyStaks_Core_ResultController extends Mage_CatalogSearch_ResultController
{

    /**
     *
     */
    public function indexAction()
    {
        if (!Mage::helper('heystaks')->isEnabled()) {
            return parent::indexAction();
        }

        if (Mage::helper('heystaks')->isTest() &&
            (!$this->getRequest()->getParam('search_type') ||
            $this->getRequest()->getParam('search_type') == 'magento')) {
            return parent::indexAction();
        }

        /* @var $heystaks HeyStaks_Core_Model_Heystaks */
        $heystaks = Mage::getModel('heystaks/heystaks');
        $results = $heystaks->getResults();

        if(!$results || $results->getSize() == 0){
            return parent::indexAction();
        }

        Mage::register('heystaks_collection', $results);

        $this->loadLayout();

        $list = $this->getLayout()->getBlock('search_result_list');
        $list->setCollection($results);

        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }
}
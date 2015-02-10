<?php

/**
 * Class HeyStaks_Core_Model_Observer
 */
class HeyStaks_Core_Model_Observer extends Varien_Event_Observer
{
    /**
     * run as a Cron Job
     */
    public function heystaks_refresh_token()
    {
        if ($this->_isActive()) {
            $heystaks = Mage::getModel('heystaks/heystaks');
            $heystaks->fetchToken();
        }
    }

    /**
     * @param $observer
     */
    public function customerSessionInit($observer)
    {
        if ($this->_isActive() && !$this->_useJsIntegration()) {
            $heystaks = Mage::getModel('heystaks/heystaks');
            $heystaks->getAnonymousUser();
        }
    }

    /**
     * @param $observer
     */
    public function controllerActionPostdispatchCatalogProductView($observer)
    {
        if ($this->_isActive() && $this->_canSendFeedback() && !$this->_useJsIntegration()) {
            if(Mage::registry('product')) {
                $request = $observer->getControllerAction()->getRequest();
                if (strpos(Mage::helper('core/url')->getCurrentUrl(), 'catalogsearch/result') == false) {
                    $heystaks = Mage::getModel('heystaks/heystaks');
                    if ($request->getParam('referer') == 'heystaks_search_results'){
                        $position = $request->getParam('position');
                        $heystaks->selectSearchResult($position);
                    } else {
                        $heystaks->sendFeedback(HeyStaks_Core_Model_Heystaks::ACTION_BROWSE);
                    }
                }
            }
        }
    }

    /**
     * @param $observer
     */
    public function updateCustomer($observer)
    {
        if ($this->_isActive() && $this->_canSendFeedback() && !$this->_useJsIntegration()) {
            $heystaks = Mage::getModel('heystaks/heystaks');
            $heystaks->updateHeystaksUser($observer->getCustomer());
        }
    }

    /**
     * @param $observer
     */
    public function customerLogout($observer)
    {
        if ($this->_isActive() && $this->_canSendFeedback() && !$this->_useJsIntegration()) {
            Mage::getSingleton('core/cookie')->delete('heystaks_user_id');
            Mage::getSingleton('core/cookie')->delete('heystaks_customer_id');
        }
    }

    /**
     * @param $observer
     */
    public function checkoutCartAddProductComplete($observer)
    {
        if ($this->_isActive() && $this->_canSendFeedback()) {
            $product = $observer->getProduct();
            $heystaks = Mage::getModel('heystaks/heystaks');
            $heystaks->sendFeedback(HeyStaks_Core_Model_Heystaks::ACTION_ADD_TO_CART,
                array('uri' => $product->getProductUrl())
            );
        }
    }

    /**
     * @param $observer
     */
    public function controllerActionPostdispatchCheckoutOnepageSuccess($observer)
    {
        if ($this->_isActive() && $this->_canSendFeedback()) {
            $session = Mage::getSingleton('checkout/session');
            $heystaks = Mage::getModel('heystaks/heystaks');

            $order = Mage::getModel('sales/order')->load($session->getLastOrderId());
            if($order->getId()){
                foreach($order->getAllVisibleItems() as $item){
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    if($product && $product instanceof Mage_Catalog_Model_Product) {
                        $heystaks->sendFeedback(HeyStaks_Core_Model_Heystaks::ACTION_PURCHASE,
                            array('uri' => $product->getProductUrl())
                        );
                    }
                }
            }
        }
    }

    /**
     * @param $observer
     */
    public function cmsPageSaveAfter($observer)
    {
        if ($this->_isActive()) {
            if (Mage::helper('heystaks')->canIncludeInSearch('page')) {
                $page = $observer->getEvent()->getObject();
                $heystaks = Mage::getModel('heystaks/heystaks');
                $heystaks->indexCmsPage(array($page));
            }
        }
    }

    /**
     * @param $observer
     */
    public function catalogCategorySaveAfter($observer)
    {
        if ($this->_isActive()) {
            if (Mage::helper('heystaks')->canIncludeInSearch('category')) {
                $category = $observer->getEvent()->getCategory();
                $heystaks = Mage::getModel('heystaks/heystaks');
                $heystaks->indexCategory(array($category));
            }
        }
    }

    /**
     * @param $observer
     */
    public function catalogProductDeleteCommitAfter($observer)
    {
        $product = $observer->getProduct();
        $heystaks = Mage::getModel('heystaks/heystaks');
        $heystaks->deleteProduct($product);
    }

    /**
     * @param $observer
     */
    public function controllerActionPredipsatchAdminhtmlSystemConfigEdit($observer)
    {
        $request = $observer->getControllerAction()->getRequest();
        if($request->getParam('section') == 'heystaks')
        {
            $index = Mage::getModel('index/process')->load('catalogsearch_fulltext', 'indexer_code');
            if($index->getMode() == 'manual'){
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('heystaks')->__('<p>We recommend setting the Catalog Search Index mode to "Update
                    on Save" so that HeyStaks has the most up to date information about the catalog.</p><p>You can change
                    this setting <a href="%s">here</a></p>',
                        Mage::helper("adminhtml")->getUrl("adminhtml/process/edit/process/" . $index->getId()))
                );
            }
        }
    }

    /**
     * @return bool
     */
    protected function _isActive()
    {
        return (bool) Mage::helper('heystaks')->isEnabled();
    }

    /**
     * @return bool
     */
    protected function _useJsIntegration()
    {
        return (bool) Mage::getStoreConfig('heystaks/general/use_js');
    }

    /**
     * @return bool
     */
    protected function _canSendFeedback()
    {
        return Mage::helper('heystaks')->areCookiesEnabled() && (bool) Mage::getStoreConfig
        ('heystaks/general/can_send_feedback');
    }
}
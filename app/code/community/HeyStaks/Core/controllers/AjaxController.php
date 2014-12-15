<?php

/**
 * Class HeyStaks_Core_AjaxController
 */
class HeyStaks_Core_AjaxController extends Mage_Core_Controller_Front_Action
{
    /**
     *
     */
    public function initAction()
    {
        $heystaks = Mage::getModel('heystaks/heystaks');
        $user = $heystaks->getAnonymousUser();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array('user' => $user)
            )
        );
    }

    /**
     *
     */
    public function feedbackAction()
    {
        $action = $this->getRequest()->getParam('action');
        $heystaks = Mage::getModel('heystaks/heystaks');
        $response = $heystaks->sendFeedback($action);

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array('response' => $response)
            )
        );
    }
}
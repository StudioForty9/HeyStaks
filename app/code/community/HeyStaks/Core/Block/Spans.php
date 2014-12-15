<?php

class HeyStaks_Core_Block_Spans extends Mage_Core_Block_Template
{
    public function getUserId()
    {
        return Mage::getSingleton('core/session')->getData('heystaks_user_id');
    }

    public function getQueryId()
    {
        return Mage::getSingleton('core/session')->getData('heystaks_query_id');
    }
}
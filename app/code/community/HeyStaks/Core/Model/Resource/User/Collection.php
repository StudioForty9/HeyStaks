<?php

/**
 * Class HeyStaks_Core_Model_Resource_User_Collection
 */
class HeyStaks_Core_Model_Resource_User_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init('heystaks/user');
    }
}
<?php

/**
 * Class HeyStaks_Core_Model_Resource_User
 */
class HeyStaks_Core_Model_Resource_User extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init('heystaks/user', 'heystaks_id');
    }
}
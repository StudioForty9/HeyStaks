<?php

/**
 * Class HeyStaks_Core_Model_User
 */
class HeyStaks_Core_Model_User extends Mage_Core_Model_Abstract
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init('heystaks/user');
    }
}
<?php

class HeyStaks_Core_Model_System_Config_Active extends Mage_Core_Model_Config_Data
{
    public function _beforeSave()
    {
        $data = $this->getData();
        $auth = $data['groups']['authentication']['fields'];
        if($this->getValue() && (!$auth['application_id']['value']
        || !$auth['admin_username']['value']
        || !$auth['admin_userpassword']['value']
        )){
            $this->setValue(0);
            //Mage::throwException('You must enter Authentication details before enabling the module');
            Mage::getSingleton('adminhtml/session')->addError(
                'You must enter Authentication details before enabling the module'
            );
        }
    }
}
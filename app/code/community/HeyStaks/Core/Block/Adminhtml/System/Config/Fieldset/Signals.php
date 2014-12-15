<?php

/**
 * Class HeyStaks_Core_Block_Adminhtml_System_Config_Fieldset_Signals
 */
class HeyStaks_Core_Block_Adminhtml_System_Config_Fieldset_Signals
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     *
     */
    public function __construct()
    {
        $this->addColumn(
            'regexp', array(
                'label' => Mage::helper('adminhtml')->__('Action'),
                'style' => 'width:120px')
        );
        $this->addColumn(
            'value', array(
                'label' => Mage::helper('adminhtml')->__('Weight'),
                'style' => 'width:120px')
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Signal');
        parent::__construct();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return '<div id="heystaks_signals_weights">' . parent::_toHtml() . '</div>';
    }
}

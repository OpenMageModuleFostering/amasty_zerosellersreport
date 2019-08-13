<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_ZeroSellers
 */
class Amasty_ZeroSellers_Block_Adminhtml_Purchased extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_controller = 'adminhtml_purchased';
        $this->_blockGroup = 'amzerosellers';
        $this->_headerText = Mage::helper('amzerosellers')->__('Zero Sellers');
        $this->_removeButton('add');
    }
}
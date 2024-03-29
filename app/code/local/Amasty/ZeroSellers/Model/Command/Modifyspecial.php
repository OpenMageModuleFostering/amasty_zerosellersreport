<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty Ltd. ( http://www.amasty.com/ )
 * @package Amasty_ZeroSellers
 */
class Amasty_ZeroSellers_Model_Command_Modifyspecial extends Amasty_ZeroSellers_Model_Command_Modifyprice
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label = 'Update Special Price';
    } 
    
    protected function _getAttrCode()
    {
        return 'special_price';
    }
}
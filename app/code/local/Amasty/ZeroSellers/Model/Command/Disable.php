<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty Ltd. ( http://www.amasty.com/ )
 * @package Amasty_ZeroSellers
 */
class Amasty_ZeroSellers_Model_Command_Disable extends Amasty_ZeroSellers_Model_Command_Abstract
{
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label      = 'Disable';
    }

    /**
     * Executes the command
     *
     * @param array $ids product ids
     * @param int $storeId store id
     * @param string $val field value
     * @return string success message if any
     */
    public function execute($ids, $storeId, $val)
    {
        parent::execute($ids, $storeId, $val ='no_need');
        $disableStatus  = 2;
        Mage::getSingleton('catalog/product_action')->updateAttributes($ids, array('status' => $disableStatus), $storeId);
        $success = Mage::helper('amzerosellers')->__("Total of %d product(s) have been disabled.",count($ids));
        return $success;
    }

}
<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Xnotif
 */

class  Amasty_ZeroSellers_Block_Adminhtml_Purchased_Renderer_Website extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    function render(Varien_Object $row)
    {
        $productWebsite = $row->getData($this->getColumn()->getIndex());
        if ($productWebsite) {
            return Mage::app()->getWebsite($productWebsite)->getName();
        }
        $product = Mage::getModel('catalog/product')->load($row->getId());
        $websiteIds =$product->getWebsiteIds();
        $result = '';
        foreach ($websiteIds as $websiteId) {
            $result .= $result ? ', ' : null;
            $result .= Mage::app()->getWebsite($websiteId)->getName();
        }
        return $result;
    }
}
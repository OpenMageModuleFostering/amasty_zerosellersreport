<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_ZeroSellers
 */
class Amasty_ZeroSellers_Adminhtml_AmzerosellersController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('report/products/amzerosellers_purchased');
        if (!Mage::helper('ambase')->isVersionLessThan(1,4)){
            $this
                ->_title($this->__('Reports'))
                ->_title($this->__('Products'))
                ->_title($this->__('Zero Sellers'));
        }
        $this->_addBreadcrumb($this->__('Products'), $this->__('Zero Sellers'));
        $block = $this->getLayout()->createBlock('amzerosellers/adminhtml_purchased');
        $this->_addContent($block);
        $this->renderLayout();
        return $this;
    }

    public function doAction()
    {
        $commandType = trim($this->getRequest()->getParam('command'));
        if (Mage::getSingleton('admin/session')->isAllowed('report/products/amzerosellers_purchased/' . $commandType)) {
            $productIds = $this->getRequest()->getParam('product_id');
            $val = $this->getRequest()->getParam('amzerosellers_value');
            $storeId = (int)$this->getRequest()->getParam('store', 0);

            if (is_array($val)) {
                $val = implode(',', $val);
            } else {
                $val = trim($val);
            }
            
            try {
                $command = Amasty_ZeroSellers_Model_Command_Abstract::factory($commandType);

                $success = $command->execute($productIds, $storeId, $val);
                if ($success) {
                    $this->_getSession()->addSuccess($success);
                }

                // show non critical erroes to the user
                foreach ($command->getErrors() as $err) {
                    $this->_getSession()->addError($err);
                }
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Error: %s', $e->getMessage()));
            }

        } else {
            $this->_getSession()->addError($this->__('Access denied.'));
        }
        $this->_redirectReferer();
        return $this;
    }

    public function exportCsvAction()
    {
        $fileName   = 'zerosellers_report.csv';
        $grid       = $this->getLayout()->createBlock('amzerosellers/adminhtml_purchased_grid');
        $fileInfo = $grid->getCsvFile();

        //ensure BOM is included for correctly opening a file in some MS EXCEL versions
        $bom = chr(239) . chr(187) . chr(191);
        $utf8 = $bom . file_get_contents($fileInfo['value']);
        file_put_contents($fileInfo['value'], $utf8);

        $this->_prepareDownloadResponse($fileName, $fileInfo);
    }

    public function exportExcelAction()
    {
        $fileName   = 'zerosellers_report.xml';
        $grid    = $this->getLayout()->createBlock('amzerosellers/adminhtml_purchased_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('amzerosellers/adminhtml_purchased_grid')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('report/products/amzerosellers_purchased');
    }
}
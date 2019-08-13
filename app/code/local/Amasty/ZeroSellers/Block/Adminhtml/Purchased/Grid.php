<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_ZeroSellers
 */
class Amasty_ZeroSellers_Block_Adminhtml_Purchased_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Not displayed in the collection.
     *
     * @var array $_forbiddenTypes
     */
    protected $_forbiddenTypes = array('grouped','configurable');

    /**
     * @var array $_massAction
     */
    protected $_massActions = array('addspecial', 'modifyspecial', 'disable');

    public function __construct()
    {
        parent::__construct();
        $this->setId('amzerosellers_purchased');
        $this->setDefaultSort('qty_ordered');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
        $threshold = (int) Mage::getStoreConfig('amzerosellers/general/threshold');
        $threshold = max(array($threshold, 0));
        $this->setDefaultFilter(array('qty_ordered' => array('to' => $threshold)));
    }

    protected function _prepareCollection()
    {
        /** @var Amasty_ZeroSellers_Model_Resource_Catalog_Product_Collection $collection */
        $collection = Mage::getModel('amzerosellers/resource_catalog_product_collection')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('price')
            ->addAttributeToFilter('type_id', array('nin' => $this->_forbiddenTypes));
        $collection->joinAttribute('special_price', 'catalog_product/special_price', 'entity_id', null, 'left');
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $collection->addQty();

        $select     = $collection->getSelect();
        $columnId   = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
        $dir        = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
        if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
            $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
            $select->order($columnId . ' ' . $dir);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _addColumnFilterToCollection($column)
    {
        if ($collection = $this->getCollection()) {
            if ($column->getId() == 'websites') {
                $collection->joinField('websites',
                    'catalog/product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left');
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }


    /**
     *  Sets sorting order by some column && fix sorting by SUM(*) column issue
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _setCollectionOrder($column)
    {
        $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        if ($columnIndex == 'qty_ordered') {
            $collection = $this->getCollection();
            if ($collection) {
                $collection
                    ->setOrder($columnIndex, strtoupper($column->getDir()))
                    ->setOrder('sku', 'ASC');
            }
        }

        return parent::_setCollectionOrder($column);
    }

    protected function _prepareColumns()
    {
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        $hlp =  Mage::helper('amzerosellers');

        $this->addColumn('qty_ordered', array(
            'header'    => $hlp->__('Purchased Qty'),
            'type'      => 'number',
            'index'     => 'qty_ordered',
            'width'     => '15px',
            'filter_condition_callback' => array($this, '_orderedQtyFilter'),
        ));

        $this->addColumn('qty', array(
            'header'    => $hlp->__('Stock Qty'),
            'type'      => 'number',
            'index'     => 'qty',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('websites',
                array(
                    'header'    => $hlp->__('Websites'),
                    'width'     => '120px',
                    'sortable'  => false,
                    'index'     => 'websites',
                    'type'      => 'options',
                    //collection filters are broken after call $this->getCollection()->addWebsiteNamesToResult(), using website renderer instead
                    'renderer'  => 'Amasty_ZeroSellers_Block_Adminhtml_Purchased_Renderer_Website',
                    'options'   => Mage::getModel('core/website')->getCollection()->toOptionHash(),
                ));
        }

        $allowedTypes = Mage::getSingleton('catalog/product_type')->getOptionArray();
        foreach ($this->_forbiddenTypes as $forbiddenType) {
            unset($allowedTypes[$forbiddenType]);
        }
        $this->addColumn('type_id', array(
                'header'    => $hlp->__('Type'),
                'width'     => '100px',
                'index'     => 'type_id',
                'type'      => 'options',
                'options'   => $allowedTypes,
        ));

        $this->addColumn('name', array(
            'header'    => $hlp->__('Product Name'),
            'type'      => 'text',
            'index'     => 'name',
        ));

        $this->addColumn('sku', array(
            'header'    => $hlp->__('SKU'),
            'index'     => 'sku',
        ));

        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->addColumn('price', array(
                'header'        => $hlp->__('Price'),
                'type'          => 'price',
                'currency_code' => $currencyCode,
                'index'         => 'price',
        ));

        $this->addColumn('special_price', array(
                'header'        => $hlp->__('Special Price'),
                'type'          => 'price',
                'currency_code' => $currencyCode,
                'index'         => 'special_price',
        ));

        $this->addColumn('visibility', array(
            'header'    => $hlp->__('Visibility'),
            'width'     => '150px',
            'index'     => 'visibility',
            'type'      => 'options',
            'options'   => Mage::getModel('catalog/product_visibility')->getOptionArray(),
        ));

        $this->addColumn('status', array(
                'header'    => $hlp->__('Status'),
                'width'     => '100px',
                'index'     => 'status',
                'type'      => 'options',
                'options'   => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));

        $this->addColumn('action_view', array(
            'header'    => $hlp->__('View'),
            'width'     => '50px',
            'sortable'  => false,
            'filter'    => false,
            'type'      => 'action',
            'is_system' => true,
            'actions'   => array(
                array(
                    'url'     => $this->getUrl('adminhtml/catalog_product/edit', array('id' => '$entity_id')),
                    'caption' => $hlp->__('View'),
                )
            )
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $block = $this->getMassactionBlock();
        $block->setFormFieldName('product_id');
        foreach ($this->_massActions as $type) {
            if (Mage::getSingleton('admin/session')->isAllowed('report/products/amzerosellers_purchased/' . $type)) {
                $command = Amasty_ZeroSellers_Model_Command_Abstract::factory($type);
                $command->addAction($block);
            }
        }
        return $this;
    }

    protected function _orderedQtyFilter($collection, $column) {
        $value = $column->getFilter()->getValue();
        if ($value === NULL) {
            return $this;
        }
        if (isset($value['from'])) {
            $collection->getSelect()->having('qty_ordered >= ' . $value['from']);
        }
        if (isset($value['to'])) {
            $collection->getSelect()->having('qty_ordered <= ' . $value['to']);
        }

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
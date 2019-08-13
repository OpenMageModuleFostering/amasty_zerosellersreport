<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_ZeroSellers
 */
class Amasty_ZeroSellers_Model_Resource_Catalog_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    protected $_qtyOrderedSelectCondition = 'COALESCE(SUM(o_items.qty_ordered),0)';

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->_addOrderedQty();
        return $this;
    }

    /**
     * Get SQL for get record count && correct HAVING use.
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);

        //add qty_ordered for HAVING condition
        $countSelect->columns($this->_qtyOrderedSelectCondition . ' as qty_ordered');
        $countSelect = 'SELECT COUNT(*) FROM(' . $countSelect . ') final' ;

        return $countSelect;
    }

    /**
     * Add product qty to collection.
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function addQty()
    {
        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $this->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }

        return $this;
    }

    /**
     * Add aggregated sales/order_item.ordered_qty to collection as qty_ordered.
     *
     * @return $this
     */
    protected function _addOrderedQty()
    {
        $this->_joinOrderedQty($this->getSelect());
        return $this;
    }
    
    /**
     * Join ordered_qty.
     *
     * @param  Zend_Db_Select This Zend_Db_Select object.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    protected function _joinOrderedQty($select)
    {
        $period = MAX((int)Mage::getStoreConfig('amzerosellers/general/period'), 0);
        $select->joinLeft(
                array('o_items' => $this->getTable('sales/order_item')),
                'e.entity_id = o_items.product_id AND o_items.created_at >= (CURDATE() - INTERVAL '. $period .' DAY)',
                array('qty_ordered' => $this->_qtyOrderedSelectCondition)
            )
            ->group('e.entity_id');
        return $select;
    }

    /**
     * Retrive all ids for collection && correct HAVING use.
     *
     * @param unknown_type $limit
     * @param unknown_type $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('e.' . $this->getEntity()->getIdFieldName());
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        //add qty_ordered for HAVING condition
        $this->_joinOrderedQty($idsSelect);

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
}
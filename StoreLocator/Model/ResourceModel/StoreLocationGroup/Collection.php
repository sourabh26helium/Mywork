<?php
namespace Highgrove\StoreLocator\Model\ResourceModel\StoreLocationGroup;

use Highgrove\StoreLocator\Model\StoreLocationGroup;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'group_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            StoreLocationGroup::class,
            \Highgrove\StoreLocator\Model\ResourceModel\StoreLocationGroup::class
        );

        $this->_setIdFieldName($this->_idFieldName);
    }
}

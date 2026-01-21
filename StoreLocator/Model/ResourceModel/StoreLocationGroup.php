<?php
namespace Highgrove\StoreLocator\Model\ResourceModel;

/**
 * Class StoreLocationGroup
 */
class StoreLocationGroup extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('store_location_group', 'group_id');
    }
}

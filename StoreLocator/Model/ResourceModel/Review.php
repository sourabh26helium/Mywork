<?php
namespace Highgrove\StoreLocator\Model\ResourceModel;

/**
 * Class Review
 */
class Review extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('mageworx_location_review', 'entity_id');
    }
}

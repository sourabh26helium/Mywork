<?php
namespace Highgrove\StoreLocator\Model;

use Highgrove\StoreLocator\Api\Data\StoreLocationGroupInterface;
use Highgrove\StoreLocator\Model\ResourceModel\StoreLocationGroup as StoreLocationGroupResource;

/**
 * Class StoreLocationGroup
 */
class StoreLocationGroup extends \Magento\Framework\Model\AbstractModel implements StoreLocationGroupInterface
{
    /**
     * Init resource model.
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(StoreLocationGroupResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getGroupId(): int
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setGroupId(int $id)
    {
        $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getLocationCode(): string
    {
        return $this->getData(self::LOCATION_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setLocationCode(string $locationCode)
    {
        $this->setData(self::LOCATION_CODE, $locationCode);
    }

    /**
     * @inheritDoc
     */
    public function getLocationLink(): string
    {
        return $this->getData(self::LOCATION_LINK);
    }

    /**
     * @inheritDoc
     */
    public function setLocationLink(string $locationLink)
    {
        $this->setData(self::LOCATION_LINK, $locationLink);
    }
}

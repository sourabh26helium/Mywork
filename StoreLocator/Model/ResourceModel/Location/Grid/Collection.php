<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Highgrove\StoreLocator\Model\ResourceModel\Location\Grid;

use Magento\CatalogInventory\Model\Configuration;
use MageWorx\Locations\Model\ResourceModel\Location\Collection as LocationCollection;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Collection extends \MageWorx\Locations\Model\ResourceModel\Location\Grid\Collection
{
    protected $_eventPrefix;
    protected $_eventObject;
}

<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Highgrove\StoreLocator\Ui\DataProvider\Location\Form;

use MageWorx\Locations\Api\LocationRepositoryInterface;
use MageWorx\Locations\Api\Data\LocationInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;

/**
 * Class LocationDataProvider
 */
class LocationDataProvider extends \MageWorx\Locations\Ui\DataProvider\Location\Form\LocationDataProvider
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $loadedData;

}

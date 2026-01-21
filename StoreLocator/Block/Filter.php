<?php
namespace Highgrove\StoreLocator\Block;

use MageWorx\StoreLocator\Helper\Data;
class Filter extends \MageWorx\StoreLocator\Block\Filter
{
    /**
     * @param string $code
     * @return string|string[]|null
     */
    public function prepareCode($code)
    {
        return $this->helper->prepareCode($code);
    }
}

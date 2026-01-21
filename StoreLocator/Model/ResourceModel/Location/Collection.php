<?php
namespace Highgrove\StoreLocator\Model\ResourceModel\Location;


class Collection extends \MageWorx\Locations\Model\ResourceModel\Location\Collection
{
	public function addSearchFilters(array $filters)
    {
        if (!isset($filters['autocomplete']) || !isset($filters['radius'])) {
            return $this;
        }

        if ($this->canApplyRadiusFilter($filters)) {
            $this->addRadiusFilter($filters);
        } 
        elseif(isset($filters['region']) && $filters['region']!=''){
            $this->addRegionFilter($filters);
        }
        elseif(isset($filters['country'])){
            $this->addCountryFilter($filters);
        }
        elseif ($this->canApplySearchTextFilter($filters)) {
            $this->addSearchTextFilter($filters);
        }

        return $this;
    }
    protected function addRegionFilter($filters)
    {
        if ($filters['region']) {
            $regionDefault = $filters['region'];
            if(str_contains($regionDefault,',')) {
                $this->getSelect()->where('region = ? ', 'Queensland');
            } else {
                $this->getSelect()->where('region = ? ', $filters['region']);
            }
        }
        return $this;
    }
    protected function addCountryFilter($filters)
    {
        if ($filters['country']) {
            $this->getSelect()->where('country_id = ? ', $filters['country']);
        }
        return $this;
    }
    public function addProductIdsFilterForCheckout($ids, $addOutOfStockItems = true)
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $connection = $this->getConnection();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productRepository = $objectManager->create('\Magento\Catalog\Model\ProductRepository');
        if(count($ids)==1)
        {
            $sku        = $connection->select()->from(
            [
                'products' =>
                    $this->getTable('catalog_product_entity')
            ],
            ['sku']
            )->where('products.entity_id IN (?)', $ids);
            $this->joinProductRelation();
            $cond = '((e.assign_type = "specific_products" OR e.assign_type = "condition") ' .
                'AND relation_product.product_sku IN (?)) ';

            $having = 'COUNT(DISTINCT relation_product.product_sku) = ' . count($ids);
        }
        else
        {
            $this->joinProductRelation();
            $cond = '((e.assign_type = "specific_products" OR e.assign_type = "condition") ' .
                'AND relation_product.product_sku IN (?)) ';
            $having='';
        }
        // join MSI tables only if MSI enabled
        if ($this->msiResolver->isMsiEnabled()) {
            if(count($ids)>1)
            {
                $i=1;
                foreach($ids as $id)
                {
                    $_product = $productRepository->getById($id);
                    $productSku = $_product->getSku();
                    $this->joinSourceItemsBySku($productSku,$i);
                    $inStockCond = $this->getInStockConditionBySku($addOutOfStockItems,$i);
                    if($i>1)
                    {
                        if($i==count($ids))
                            $cond.= 'AND (e.assign_type = "products_from_source" AND source_item'.$i.'.sku  ="'.$productSku.'"'.$inStockCond . '))';
                        else
                            $cond.= 'AND (e.assign_type = "products_from_source" AND source_item'.$i.'.sku  ="'.$productSku.'"'.$inStockCond . ') ';
                        //$having.= ' OR COUNT(DISTINCT source_item'.$i.'.sku) = ' . count($ids);
                    }
                    else
                    {
                        $cond.= 'OR ((e.assign_type = "products_from_source" AND source_item'.$i.'.sku  ="'.$productSku.'"'.$inStockCond . ') ';
                        //$having.= ' OR COUNT(DISTINCT source_item'.$i.'.sku) = ' . count($ids);
                    }
                    $i++;
                }
            }
            else
            {
                $this->joinSourceItems();
                $inStockCond = $this->getInStockCondition($addOutOfStockItems);
                $cond        .= 'OR (e.assign_type = "products_from_source" AND source_item.sku  IN (?)' . $inStockCond . ') ';
                $having      .= ' OR COUNT(DISTINCT source_item.sku) = ' . count($ids);
                $cond   .= 'OR e.assign_type = "all_products"';
                $having .= ' OR e.assign_type = "all_products"';
            }
        }
        if(count($ids)>1)
        {
             $this->getSelect()->where($cond);
        }
        else
        {
             $this->getSelect()->where($cond, $sku);
        }
        if (count($ids) > 1) {
            if($having)
            {
                $this->getSelect()->having($having);
            }
            
        }
        return $this;
    }
    protected function joinSourceItemsBySku($productSku,$incrementer)
    {
        //if (!$this->getFlag('is_source_items_table_joined')) 
        //{
            $this->setFlag('is_source_items_table_joined', true);
            $this->getSelect()->joinLeft(
                ['source_item'.$incrementer => $this->getTable('inventory_source_item')],
                'e.source_code = source_item'.$incrementer.'.source_code',
                []
            );//->group('e.code');
        //}
    }
    protected function getInStockConditionBySku($addOutOfStockItems,$incrementer)
    {
        $inStockCond = '';

        if (!$addOutOfStockItems) {
            $inStockCond = ' AND source_item'.$incrementer.'.status = 1';

            if ($this->inventoryConfig->getManageStock()) {
                $inStockCond = ' AND source_item'.$incrementer.'.quantity > 0';
            }
        }

        return $inStockCond;
    }
    protected function getInStockCondition($addOutOfStockItems)
    {
        //updated to only return stores which have inventory on product page
        $inStockCond = '';

        if (!$addOutOfStockItems) {
            $inStockCond = ' AND source_item.status = 1';
 
            //if ($this->inventoryConfig->getManageStock()) {
                //$inStockCond = ' AND source_item.quantity > 0';
            //}
        }
        $inStockCond = ' AND source_item.quantity > 0';
        return $inStockCond;
    }
}
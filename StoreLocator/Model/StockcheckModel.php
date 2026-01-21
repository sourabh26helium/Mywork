<?php
namespace Highgrove\StoreLocator\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

class StockcheckModel
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

     /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
    }

    /**
     * Retrieves links that are assigned to $stockId
     *
     * @param string $sku
     * @param string $storeCode
     * @return SourceItemInterface[]
     */
    public function getSourceItemDetailBySKU(string $sku, string $storeCode = ''): array
    {
        $searchBuilder = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku);

        if (!empty($storeCode)) {
            $searchBuilder->addFilter(SourceItemInterface::SOURCE_CODE, $storeCode);
        }

        $searchCriteria = $searchBuilder->create();
        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }
	/**
	 * Get available quantity for a product in a specific source.
	 *
	 * @param string $sku
	 * @param string $sourceCode
	 * @return float|null
	 */
	public function getProductQuantityBySource(string $sku, string $sourceCode): ?float
	{
		$searchCriteria = $this->searchCriteriaBuilder
			->addFilter('sku', $sku)
			->addFilter('source_code', $sourceCode)
			->create();

		$sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
		foreach ($sourceItems as $sourceItem) {
			if ($sourceItem->getSourceCode() === $sourceCode) {
				return $sourceItem->getQuantity();
			}
		}

		return null; // Or throw an exception if the source item is not found
	}
}

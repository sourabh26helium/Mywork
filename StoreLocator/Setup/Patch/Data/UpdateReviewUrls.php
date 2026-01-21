<?php
namespace Highgrove\StoreLocator\Setup\Patch\Data;

use Highgrove\StoreLocator\Api\ReviewRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateReviewUrls
 */
class UpdateReviewUrls implements DataPatchInterface
{
    const JSON_FILE = 'store-review-urls';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var State
     */
    private $state;

    /**
     * @var ReviewRepositoryInterface
     */
    private $reviewRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateStoreLocationCodes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReadFactory $readFactory
     * @param Reader $reader
     * @param LocationRepositoryInterface $locationRepository
     * @param State $state
     * @param ReviewRepositoryInterface $reviewRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ReadFactory $readFactory,
        Reader $reader,
        LocationRepositoryInterface $locationRepository,
        State $state,
        ReviewRepositoryInterface $reviewRepository,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->readFactory = $readFactory;
        $this->reader = $reader;
        $this->locationRepository = $locationRepository;
        $this->state = $state;
        $this->reviewRepository = $reviewRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [
            CreateStoreLocations::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return UpdateStoreLocationCodes|void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            $this->logger->critical("Error setting area code");
            return;
        }

        $jsonFile = $this->getJsonFile();
        if (!$jsonFile) {
            $this->moduleDataSetup->endSetup();
            return;
        }

        $json = $jsonFile->readAll();
        $storeData = json_decode($json);
        if (!$storeData) {
            $this->moduleDataSetup->endSetup();
            return;
        }

        foreach ($storeData as $store) {
            try {
                $location = $this->locationRepository->getByCode($store->code);
                $review = $this->reviewRepository->getReviewByLocationId($location->getId());
                $review->setReviewUrl($store->review_url);
                $this->reviewRepository->save($review);
            } catch (\Exception $e) {
                $this->logger->critical("Error updating store: {$e->getMessage()}");
                return;
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return \Magento\Framework\Filesystem\File\ReadInterface
     */
    private function getJsonFile(): \Magento\Framework\Filesystem\File\ReadInterface
    {
        $moduleSetupPath = $this->reader->getModuleDir(Dir::MODULE_SETUP_DIR, 'Highgrove_StoreLocator');
        $jsonPath = sprintf('%s/%s%s.json', $moduleSetupPath, 'Patch/Data/', self::JSON_FILE);
        return $this->readFactory->create($jsonPath, DriverPool::FILE);
    }
}

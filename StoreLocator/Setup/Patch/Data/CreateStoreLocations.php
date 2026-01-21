<?php
namespace Highgrove\StoreLocator\Setup\Patch\Data;

use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManager;
use MageWorx\Locations\Model\LocationFactory;
use MageWorx\Locations\Model\LocationRepository;
use Psr\Log\LoggerInterface;

/**
 * Class CreateStoreLocations
 */
class CreateStoreLocations implements DataPatchInterface
{
    const JSON_FILE = 'store-locations';

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
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateStoreLocations constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReadFactory $readFactory
     * @param Reader $reader
     * @param LocationFactory $locationFactory
     * @param LocationRepository $locationRepository
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ReadFactory $readFactory,
        Reader $reader,
        LocationFactory $locationFactory,
        LocationRepository $locationRepository,
        StoreManager $storeManager,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->readFactory = $readFactory;
        $this->reader = $reader;
        $this->locationFactory = $locationFactory;
        $this->locationRepository = $locationRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return CreateStoreLocations|void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $defaultStore = $this->storeManager->getDefaultStoreView();

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
                $location = $this->locationFactory->create();
                $arrData = [
                    'name' => $store->name,
                    'code' => $store->source_code,
                    'active' => 1,
                    'country_id' => $store->country_id,
                    'region' => $store->region,
                    'city' => $store->city,
                    'address' => $store->street,
                    'postcode' => $store->postcode,
                    'phone_number' => $store->phone,
                    'latitude' => $store->latitude,
                    'longitude' => $store->longitude
                ];

                $location->setStoreIds([$defaultStore->getId()]);
                $location->addData($arrData);

                $this->locationRepository->save($location);
            } catch (\Exception $e) {
                $this->logger->error("Error creating store: {$e->getMessage()}");
                continue;
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

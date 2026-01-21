<?php
namespace Highgrove\StoreLocator\Setup\Patch\Data;

use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;

/**
 * Class CreateStoreLocations
 */
class CreateCustomLocationData implements DataPatchInterface
{
    const JSON_FILE = 'store-locations-custom-data';

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
        StoreManager $storeManager,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->readFactory = $readFactory;
        $this->reader = $reader;
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

        //$defaultStore = $this->storeManager->getDefaultStoreView();

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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $locationModel = $objectManager->create('Highgrove\LocationPages\Model\Locationdata');
        foreach ($storeData as $store) {
            $arrData = [
                'location' => $store->location,
                'location_text' => $store->location_text,
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'viewport' => $store->viewport,
                'drop_text' => $store->drop_text
            ];
            $locationModel->setData($arrData);
            $locationModel->save();
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

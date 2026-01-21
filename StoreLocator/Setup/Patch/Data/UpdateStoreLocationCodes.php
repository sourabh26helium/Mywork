<?php
namespace Highgrove\StoreLocator\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\Locations\Api\Data\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateStoreLocationCodes
 */
class UpdateStoreLocationCodes implements DataPatchInterface
{
    const JSON_FILE = 'store-location-codes';

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
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateStoreLocationCodes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReadFactory $readFactory
     * @param Reader $reader
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ReadFactory $readFactory,
        Reader $reader,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->readFactory = $readFactory;
        $this->reader = $reader;
        $this->resourceConnection = $resourceConnection;
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
                $connection = $this->resourceConnection->getConnection();
                $table = $connection->getTableName(LocationInterface::ENTITY_TABLE);
                $query = "UPDATE $table SET CODE = :code WHERE CODE = :source_code;";
                $connection->query($query, [':code' => $store->code, ':source_code' => $store->source_code]);
            } catch (\Exception $e) {
                $this->logger->error("Error updating store: {$e->getMessage()}");
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

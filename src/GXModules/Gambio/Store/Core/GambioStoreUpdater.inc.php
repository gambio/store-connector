<?php
/* --------------------------------------------------------------
   GambioStoreUpdater.php 2020-05-08
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../GambioStoreConnector.inc.php';


/**
 * Class GambioStoreUpdater
 *
 * Updates the Gambio Store Connector
 */
class GambioStoreUpdater
{
    /**
     * @var \GambioStoreConfiguration
     */
    private $configuration;
    
    /**
     * @var \GambioStoreDatabase
     */
    private $database;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    /**
     * @var \GambioStoreConnector
     */
    private $connector;
    
    
    /**
     * GambioStoreUpdater constructor.
     *
     * @param \GambioStoreConfiguration $configuration
     * @param \GambioStoreDatabase      $database
     * @param \GambioStoreConnector     $connector
     */
    public function __construct(
        GambioStoreConfiguration $configuration,
        GambioStoreDatabase $database,
        GambioStoreFileSystem $fileSystem,
        GambioStoreConnector $connector
    ) {
        
        $this->configuration = $configuration;
        $this->database      = $database;
        $this->fileSystem    = $fileSystem;
        $this->connector     = $connector;
    }
    
    
    /**
     * Runs the updates for the StoreConnector
     */
    public function update()
    {
        $this->removeOldStoreFilesInShop();
        $this->updateMenu();
        $this->createDatabaseKeysIfNotExists();
        $this->createCacheTableIfNotExists();
    }
    
    
    /**
     * Removes the old menu entry for shops that were still shipped with the Store
     */
    public function updateMenu()
    {
        $menuPath = $this->fileSystem->getShopDirectory() . '/system/conf/admin_menu/gambio_menu.xml';
        
        $menuContent = file_get_contents($menuPath);
        
        $gambioMenuRegex = '/.<menugroup id="BOX_HEADING_GAMBIO_STORE.*\s.*\s.*\s.*\s.<\/menugroup>/i';
        
        $menuContent = preg_replace($gambioMenuRegex, '', $menuContent);
        
        file_put_contents($menuPath, $menuContent);
    }
    
    
    /**
     * Creates the necessary database values for the Store
     */
    public function createDatabaseKeysIfNotExists()
    {
        if (!$this->configuration->has('GAMBIO_STORE_URL')) {
            $this->configuration->create('GAMBIO_STORE_URL', 'https://store.gambio.com/a');
        }
        
        if (!$this->configuration->has('GAMBIO_STORE_TOKEN')) {
            $gambioToken = $this->connector->generateToken();
            $this->configuration->create('GAMBIO_STORE_TOKEN', $gambioToken);
        }
        
        if (!$this->configuration->has('GAMBIO_STORE_IS_REGISTERED')) {
            $this->configuration->create('GAMBIO_STORE_IS_REGISTERED', 'false');
        }
        
        if (!$this->configuration->has('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING')) {
            if ($this->configuration->has('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING')) {
                $value = $this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING');
                $this->configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', $value);
            } else {
                $this->configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', 'false');
            }
        }
    }
    
    
    /**
     * Ensures the cache table exists in the database
     */
    public function createCacheTableIfNotExists()
    {
        $this->database->query('
                CREATE TABLE IF NOT EXISTS `gambio_store_cache` (
                `id` INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        	      `cache_key` VARCHAR(30) NOT NULL,
        	      `cache_value` TEXT NOT NULL
                ) ENGINE=INNODB
            ');
    }
    
    
    /**
     * Removes the old files inside the Shop of the previous Store implementation
     */
    private function removeOldStoreFilesInShop()
    {
        $this->fileSystem->remove('GXMainComponents/Controllers/HttpView/Admin/GambioStoreController.inc.php');
        $this->fileSystem->remove('GXMainComponents/Controllers/HttpView/AdminAjax/GambioStoreAjaxController.inc.php');
        $this->fileSystem->remove('GXMainComponents/Controllers/HttpView/Shop/GambioStoreCallbackController.inc.php');
        $this->fileSystem->remove('GXMainComponents/Extensions/GambioStore');
        $this->fileSystem->remove('admin/html/content/gambio_store');
        $this->fileSystem->remove('admin/javascript/engine/controllers/gambio_store');
        $this->fileSystem->remove('lang/german/original_sections/admin/gambio_store');
        $this->fileSystem->remove('lang/english/original_sections/admin/gambio_store');
    }
}

$connector = GambioStoreConnector::getInstance();

$configuration = $connector->getConfiguration();
$database      = $connector->getDatabase();
$fileSystem    = $connector->getFileSystem();

$updater = new GambioStoreUpdater($configuration, $database, $fileSystem, $connector);
$updater->update();

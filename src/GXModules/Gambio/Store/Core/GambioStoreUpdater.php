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

// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreUpdater_included')) {
        
        define('GambioStoreUpdater_included', true);
    
        /**
         * Class GambioStoreUpdater
         *
         * The class is used to perform a self-update for the GambioConnector package.
         * There is an older version of the GambioConnector package that had been updating via gambio_updater project.
         * Since the gambio_updater project is out of the responsibility of the GambioConnector package,
         * the main goal of introducing the new version is to lessen the dependency between them.
         * The class contains methods to perform various updates, such as updating the menu or upgrading database entries.
         * It should be noted that it is possible to repeatedly execute the class.
         *
         */
        class GambioStoreUpdater
        {
            /**
             * @var \GambioStoreConfigurationFacade
             */
            private $configuration;
            
            /**
             * @var \GambioStoreDatabaseFacade
             */
            private $database;
            
            /**
             * @var \GambioStoreFileSystemFacade
             */
            private $fileSystem;
            
            
            /**
             * GambioStoreUpdater constructor.
             *
             * @param \GambioStoreConfigurationFacade $configuration
             * @param \GambioStoreDatabaseFacade      $database
             * @param \GambioStoreFileSystemFacade    $fileSystem
             */
            public function __construct(
                GambioStoreConfigurationFacade $configuration,
                GambioStoreDatabaseFacade $database,
                GambioStoreFileSystemFacade $fileSystem
            ) {
                
                $this->configuration = $configuration;
                $this->database      = $database;
                $this->fileSystem    = $fileSystem;
            }
            
            
            /**
             * Runs the updates for the StoreConnector
             */
            public function update()
            {
                $this->ensureLogsAreWritable();
                $this->ensureStoreFolderIsWritableForUpdates();
                $this->removeOldStoreFilesInShop();
                $this->updateMenu();
                $this->createDatabaseKeysIfNotExists();
                $this->createCacheTableIfNotExists();
                $this->updateBoilerplateScss();
            }
            
            
            /**
             * Removes the import in the _boilerplate.scss in older shops that still shipped with the Store
             */
            private function updateBoilerplateScss()
            {
                $boilerplatePath = $this->fileSystem->getShopDirectory() . '/admin/styles/admin/_boilerplate.scss';
                
                $boilerplateContent = file_get_contents($boilerplatePath);
                
                $importAppStoreRegex = '/@import "app_store\/app_store";/i';
                
                $boilerplateContent = preg_replace($importAppStoreRegex, '', $boilerplateContent);
                
                $importGambioStoreRegex = '/@import "gambio_store\/gambio_store";/i';
                
                $boilerplateContent = preg_replace($importGambioStoreRegex, '', $boilerplateContent);
                
                file_put_contents($boilerplatePath, $boilerplateContent);
            }
            
            
            /**
             * Make sure our Logs directory is writable
             */
            private function ensureLogsAreWritable()
            {
                @chmod($this->fileSystem->getShopDirectory() . '/GXModules/Gambio/Store/Logs', 0777);
            }
            
            
            /**
             * Removes the old menu entry for shops that were still shipped with the Store
             */
            private function updateMenu()
            {
                $menuPath = $this->fileSystem->getShopDirectory() . '/system/conf/admin_menu/gambio_menu.xml';
                
                $menuContent = file_get_contents($menuPath);
                
                $gambioStoreMenuRegex = '/.<menugroup id="BOX_HEADING_GAMBIO_STORE.*\s.*\s.*\s.*\s.<\/menugroup>/i';
                
                $menuContent = preg_replace($gambioStoreMenuRegex, '', $menuContent);
                
                $appStoreMenuRegex = '/.<menugroup id="BOX_HEADING_APP_STORE.*\s.*\s.*\s.*\s.<\/menugroup>/i';
                
                $menuContent = preg_replace($appStoreMenuRegex, '', $menuContent);
                
                file_put_contents($menuPath, $menuContent);
            }
            
            
            /**
             * Creates the necessary database values for the Store
             */
            private function createDatabaseKeysIfNotExists()
            {
                if ($this->configuration->has('APP_STORE_URL') && !$this->configuration->has('GAMBIO_STORE_URL')) {
                    $this->configuration->create('GAMBIO_STORE_URL', $this->configuration->get('APP_STORE_URL'));
                    $this->configuration->remove('APP_STORE_URL');
                }
                
                if ($this->configuration->has('APP_STORE_TOKEN') && !$this->configuration->has('GAMBIO_STORE_TOKEN')) {
                    $token = $this->configuration->get('APP_STORE_TOKEN');
                    if (!empty($token)) {
                        $this->configuration->create('GAMBIO_STORE_TOKEN', $token);
                    }
                    $this->configuration->remove('APP_STORE_TOKEN');
                }
                
                if ($this->configuration->has('APP_STORE_IS_REGISTERED')
                    && !$this->configuration->has('GAMBIO_STORE_IS_REGISTERED')) {
                    $this->configuration->create('GAMBIO_STORE_IS_REGISTERED',
                        $this->configuration->get('APP_STORE_IS_REGISTERED'));
                    $this->configuration->remove('APP_STORE_IS_REGISTERED');
                }
                
                if (!$this->configuration->has('GAMBIO_STORE_URL')) {
                    $this->configuration->create('GAMBIO_STORE_URL', 'https://store.gambio.com/a');
                }
                
                if (!$this->configuration->has('GAMBIO_STORE_TOKEN')) {
                    $prefix    = 'STORE';
                    $date      = date('Ymd');
                    $hash      = md5(time());
                    $suffix    = 'XX';
                    $delimiter = '-';
                    
                    $gambioToken = implode($delimiter, [$prefix, $date, $hash, $suffix]);
                    $this->configuration->create('GAMBIO_STORE_TOKEN', $gambioToken);
                }
                
                if (!$this->configuration->has('GAMBIO_STORE_IS_REGISTERED')) {
                    $this->configuration->create('GAMBIO_STORE_IS_REGISTERED', false);
                }
                
                if (!$this->configuration->has('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING')) {
                    if ($this->configuration->has('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING')) {
                        $value = $this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING');
                        $value = $value == '1' || (strcasecmp($value, 'true') == 0);
                        $this->configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', $value);
                    } else {
                        $this->configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', false);
                    }
                }
                
                if (!$this->configuration->has('GAMBIO_STORE_API_URL')) {
                    $this->configuration->create('GAMBIO_STORE_API_URL', '');
                }
            }
            
            
            /**
             * Ensures the cache table exists in the database
             */
            private function createCacheTableIfNotExists()
            {
                $this->database->query('
                CREATE TABLE IF NOT EXISTS `gambio_store_cache` (
        	      `cache_key` VARCHAR(255) NOT NULL PRIMARY KEY,
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
                $this->fileSystem->remove('admin/styles/admin/gambio_store');
                
                $this->fileSystem->remove('GXMainComponents/Controllers/HttpView/Admin/AppStoreController.inc.php');
                $this->fileSystem->remove('GXMainComponents/Controllers/HttpView/AdminAjax/AppStoreAjaxController.inc.php');
                $this->fileSystem->remove('GXMainComponents/Controllers/HttpView/Shop/AppStoreCallbackController.inc.php');
                $this->fileSystem->remove('GXMainComponents/Extensions/AppStore');
                $this->fileSystem->remove('admin/html/content/app_store');
                $this->fileSystem->remove('admin/javascript/engine/controllers/app_store');
                $this->fileSystem->remove('lang/german/original_sections/admin/app_store');
                $this->fileSystem->remove('lang/english/original_sections/admin/app_store');
                $this->fileSystem->remove('admin/styles/admin/app_store');
            }
            
            
            /**
             * Ensures the GambioStore directory is writable for future updates
             */
            private function ensureStoreFolderIsWritableForUpdates()
            {
                @chmod($this->fileSystem->getShopDirectory() . '/GXModules/Gambio/Store', 0777);
            }
        }
    }
}

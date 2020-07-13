<?php
/* --------------------------------------------------------------
   GambioStoreUpdatesFacade.php 2020-07-10
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreUpdatesFacade_included')) {
        
        define('GambioStoreUpdatesFacade_included', true);
        require_once 'GambioStoreCacheFacade.php';
        require_once 'GambioStoreShopInformationFacade.php';
        require_once 'GambioStoreFileSystemFacade.php';
        require_once 'GambioStoreHttpFacade.php';
        require_once 'GambioStoreCompatibilityFacade.php';
        require_once 'GambioStoreDatabaseFacade.php';
        require_once 'GambioStoreConfigurationFacade.php';
        require_once __DIR__ . '/../../GambioStoreConnector.inc.php';
        
        require_once __DIR__ . '/../Exceptions/GambioStoreException.inc.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreUpdatesNotRetrievableException.inc.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreUpdatesNotInstalledException.inc.php';
        
        /**
         * Class GambioStoreUpdatesFacade
         *
         * This class allows clients to communicate with the api.
         */
        class GambioStoreUpdatesFacade
        {
            /**
             * @var \GambioStoreCacheFacade
             */
            private $cache;
            
            /**
             * @var \GambioStoreHttpFacade
             */
            private $http;
            
            /**
             * @var \GambioStoreShopInformationFacade
             */
            private $shopInformation;
            
            /**
             * @var \GambioStoreConfigurationFacade
             */
            private $configuration;
            
            /**
             * @var \GambioStoreLoggerFacade
             */
            private $logger;
            
            
            /**
             * GambioStoreUpdatesFacade constructor.
             */
            public function __construct()
            {
                $fileSystem            = new GambioStoreFileSystemFacade();
                $database              = GambioStoreDatabaseFacade::connect($fileSystem);
                $this->http            = new GambioStoreHttpFacade();
                $this->cache           = new GambioStoreCacheFacade($database);
                $this->shopInformation = new GambioStoreShopInformationFacade($database, $fileSystem);
                $this->logger          = new GambioStoreLoggerFacade($this->cache);
                $this->configuration   = new GambioStoreConfigurationFacade($database,
                    new GambioStoreCompatibilityFacade($database));
            }
            
            
            /**
             * Retrieves the number of available updates for the current shop version from the store.
             * Note that this returns an empty array silently if either:
             *  - curl is missing
             *  - not registered to the store
             *  - data processing not accepted
             *
             * @return array
             * @throws \GambioStoreUpdatesNotRetrievableException
             */
            public static function fetchAvailableUpdates()
            {
                return GambioStoreConnector::getInstance()->fetchAvailableUpdates();
            }
            
            
            /**
             *
             * Retrieves the number of available updates.
             * Use this method if the number of updates is to be queried frequently,
             * as this will only query the api once per day or if the method to clear the cache was invoked.
             *
             * @return int
             * @throws \GambioStoreUpdatesNotRetrievableException
             * @throws \Exception
             */
            public function getCachedNumberOfUpdates()
            {
                $now = new DateTime();
                
                if (!$this->cache->has('GAMBIO_STORE_UPDATE_COUNT_DATE')) {
                    $updateCount = count(self::fetchAvailableUpdates());
                    $this->cache->set('GAMBIO_STORE_UPDATE_COUNT', $updateCount);
                    $this->cache->set('GAMBIO_STORE_UPDATE_COUNT_DATE', $now->format('Y-m-d'));
                    
                    return $updateCount;
                }
                
                $then = DateTime::createFromFormat('Y-m-d', $this->cache->get('GAMBIO_STORE_UPDATE_COUNT_DATE'));
                
                if ($now->diff($then)->days > 0) {
                    $updateCount = count(self::fetchAvailableUpdates());
                    $this->cache->set('GAMBIO_STORE_UPDATE_COUNT', $updateCount);
                    $this->cache->set('GAMBIO_STORE_UPDATE_COUNT_DATE', $now->format('Y-m-d'));
                    
                    return $updateCount;
                }
                
                return $this->cache->get('GAMBIO_STORE_UPDATE_COUNT');
            }
            
            
            /**
             * This method installs updates as queried from the store-api.
             *
             * @param array $updates The updates to install.
             *
             * @throws \GambioStoreUpdatesNotInstalledException in case of failure.
             * @see \GambioStoreUpdatesFacade::fetchAvailableUpdates()
             *
             */
            public function installUpdates(array $updates)
            {
                try {
                    foreach ($updates as $update) {
                        GambioStoreConnector::getInstance()->installPackage($update);
                    }
                } catch (\Exception $exception) {
                    $message = 'An update could not be installed!';
                    $this->logger->error($message, [
                        'error' => [
                            'code'    => $exception->getCode(),
                            'message' => $exception->getMessage(),
                            'file'    => $exception->getFile(),
                            'line'    => $exception->getLine()
                        ],
                    ]);
                    throw new GambioStoreUpdatesNotInstalledException($message, $exception->getCode(),
                        ["updates" => $updates], $exception);
                }
            }
            
            
            /**
             * Clears the number of cached updates, so that subsequent queries to it will return a fresh value.
             *
             * @return void
             * @throws \GambioStoreCacheException
             */
            public function clearCachedNumberOfUpdates()
            {
                if ($this->cache->has('GAMBIO_STORE_UPDATE_COUNT_DATE')) {
                    $this->cache->delete('GAMBIO_STORE_UPDATE_COUNT_DATE');
                }
            }
        }
    }
}

<?php
/* --------------------------------------------------------------
   GambioStoreUpdatesFacade.php 2020-07-08
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
        require_once 'GambioStoreHttpFacade.php';
        require_once 'GambioStoreConfigurationFacade.php';
        
        require_once __DIR__ . '/../Exceptions/GambioStoreException.inc.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreUpdatesNotRetrievableException.inc.php';
        
        /**
         * Class GambioStoreUpdatesFacade
         *
         * This class allows clients to communicate with the api.
         */
        class GambioStoreUpdatesFacade
        {
            /**
             * The URL of the store api.
             */
            const STORE_API_URL = 'https://store.gambio.com';
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
             * GambioStoreUpdatesFacade constructor.
             *
             * @param \GambioStoreCache $cache
             */
            public function __construct(
                GambioStoreHttpFacade $http,
                GambioStoreCacheFacade $cache,
                GambioStoreShopInformationFacade $shopInformation,
                GambioStoreConfigurationFacade $configuration
            ) {
                $this->http            = $http;
                $this->cache           = $cache;
                $this->shopInformation = $shopInformation;
                $this->configuration   = $configuration;
            }
            
            
            /**
             * Retrieves the number of available updates for the current shop version from the store.
             *
             * @return array
             */
            public function fetchAvailableUpdates()
            {
                if (!$this->configuration->has('GAMBIO_STORE_IS_REGISTERED')
                    || $this->configuration->get('GAMBIO_STORE_IS_REGISTERED') !== true
                    || !$this->configuration->has('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING')
                    || $this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') !== true) {
                    return [];
                }
                
                try {
                    $shopInformation = $this->shopInformation->getShopInformation();
                } catch (GambioStoreException $e) {
                    throw new GambioStoreUpdatesNotRetrievableException("Could not fetch shop information during update-fetching!",
                        $e->getCode(), $e->getContext(), $e);
                }
                
                try {
                    $response = $this->http->post(self::STORE_API_URL . '/merchant_packages', $shopInformation)
                                           ->getBody();
                } catch (GambioStoreHttpErrorException $e) {
                    throw new GambioStoreUpdatesNotRetrievableException("Network failure while trying to fetch updates.",
                        $e->getCode(), $e->getContext(), $e);
                }
                
                if (!is_array($response) || !array_key_exists('updates', $response)
                    || !is_array($response['updates'])) {
                    throw new GambioStoreUpdatesNotRetrievableException("The API returned invalid updates!", 0,
                        ['response' => $response]);
                }
                
                return $response['updates'];
            }
            
            
            /**
             * Retrieves the number of available updates.
             * Use this method if the number of updates is to be queried frequently,
             * as this will only query the api once per day or if the method to clear the cache was invoked.
             *
             * @return int
             * @throws \GambioStoreUpdatesNotRetrievableException
             * @see \GambioStoreUpdatesFacade::clearCachedNumberOfUpdates()
             */
            public function getCachedNumberOfUpdates()
            {
                $now = new DateTime();
                if (!$this->configuration->has('GAMBIO_STORE_LAST_UPDATE_COUNT_FETCH_DATE')) {
                    $updateCount = count($this->fetchAvailableUpdates());
                    $this->configuration->set('GAMBIO_STORE_LAST_UPDATE_COUNT', $updateCount);
                    $this->configuration->set('GAMBIO_STORE_LAST_UPDATE_COUNT_FETCH_DATE', $now->format('Y-m-d'));
                    
                    return $updateCount;
                }
                
                $then = new DateTime($this->configuration->get('GAMBIO_STORE_LAST_UPDATE_COUNT_FETCH_DATE'));
                if ($now->diff($then)->days > 0) {
                    $updateCount = count($this->fetchAvailableUpdates());
                    $this->configuration->set('GAMBIO_STORE_LAST_UPDATE_COUNT', $updateCount);
                    $this->configuration->set('GAMBIO_STORE_LAST_UPDATE_COUNT_FETCH_DATE', $now->format('Y-m-d'));
                    
                    return $updateCount;
                }
                
                return $this->configuration->get('GAMBIO_STORE_LAST_UPDATE_COUNT');
            }
            
            
            /**
             * Clears the number of cached updates, so that subsequent queries to it will return a fresh value.
             *
             * @return void
             * @see \GambioStoreUpdatesFacade::getCachedNumberOfUpdates()
             */
            public function clearCachedNumberOfUpdates()
            {
                $this->configuration->remove('GAMBIO_STORE_LAST_UPDATE_COUNT_FETCH_DATE');
            }
        }
    }
}

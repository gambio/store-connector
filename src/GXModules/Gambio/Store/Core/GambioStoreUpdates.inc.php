<?php
/* --------------------------------------------------------------
   GambioStoreUpdates.php 2020-07-08
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
require_once 'GambioStoreCache.inc.php';
require_once 'GambioStoreShopInformation.inc.php';
require_once 'GambioStoreHttp.inc.php';
require_once 'GambioStoreConfiguration.inc.php';
require_once 'Exceptions/GambioStoreException.inc.php';
require_once 'Exceptions/GambioStoreUpdatesNotRetrievableException.inc.php';
require_once 'Exceptions/GambioStoreUpdatesNotInstalledException.inc.php';
require_once __DIR__ . '/../GambioStoreConnector.inc.php';

/**
 * Class GambioStoreUpdates
 *
 * This class allows clients to communicate with the api.
 */
class GambioStoreUpdates
{
    /**
     * The URL of the store api.
     */
    const STORE_API_URL = 'https://store.gambio.com';
    /**
     * @var \GambioStoreCache
     */
    private $cache;
    /**
     * @var \GambioStoreHttp
     */
    private $http;
    /**
     * @var \GambioStoreShopInformation
     */
    private $shopInformation;
    /**
     * @var \GambioStoreConfiguration
     */
    private $configuration;
    
    
    /**
     * GambioStoreUpdates constructor.
     *
     * @param \GambioStoreCache $cache
     */
    public function __construct(
        GambioStoreHttp $http,
        GambioStoreCache $cache,
        GambioStoreShopInformation $shopInformation,
        GambioStoreConfiguration $configuration
    ) {
        $this->http            = $http;
        $this->cache           = $cache;
        $this->shopInformation = $shopInformation;
        $this->configuration   = $configuration;
    }
    
    
    /**
     * Retrieves the number of available updates for the current shop version from the store.
     * Note that this returns an empty array silently if either:
     *  - curl is missing
     *  - not registered to the store
     *  - data processing not accepted
     * @return array
     */
    public function fetchAvailableUpdates()
    {
        if (!extension_loaded('curl')) {
            return [];
        }
        
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
     * This method installs updates as queried from the store-api.
     *
     * @see \GambioStoreUpdates::fetchAvailableUpdates()
     *
     * @param array $updates The updates to install.
     *
     * @throws \GambioStoreUpdatesNotInstalledException in case of failure.
     */
    public function installUpdates(array $updates) {
        try {
            foreach ($updates as $update) {
                GambioStoreConnector::getInstance()->installPackage($update);
            }
        } catch(\Exception $e) {
            throw new GambioStoreUpdatesNotInstalledException("An update could not be installed!", $e->getCode(), ["updates" => $updates], $e);
        }
    } 
    
    
    /**
     * Retrieves the number of available updates.
     * Use this method if the number of updates is to be queried frequently,
     * as this will only query the api once per day or if the method to clear the cache was invoked.
     *
     * @return int
     * @throws \GambioStoreUpdatesNotRetrievableException
     * @see \GambioStoreUpdates::clearCachedNumberOfUpdates()
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
     * @see \GambioStoreUpdates::getCachedNumberOfUpdates()
     */
    public function clearCachedNumberOfUpdates()
    {
        $this->configuration->remove('GAMBIO_STORE_LAST_UPDATE_COUNT_FETCH_DATE');
    }
}

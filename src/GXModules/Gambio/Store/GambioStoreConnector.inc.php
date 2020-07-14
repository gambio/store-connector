<?php
/* --------------------------------------------------------------
   GambioStoreConnector.php 2020-06-11
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'GambioStoreConnector.inc.php';
require_once 'Core/GambioStoreCompatibility.inc.php';
require_once 'Core/GambioStoreDatabase.inc.php';
require_once 'Core/GambioStoreLogger.inc.php';
require_once 'Core/GambioStoreConfiguration.inc.php';
require_once 'Core/GambioStoreThemes.inc.php';
require_once 'Core/GambioStoreFileSystem.inc.php';
require_once 'Core/GambioStoreShopInformation.inc.php';
require_once 'Core/GambioStoreCache.inc.php';
require_once 'Core/GambioStoreBackup.inc.php';
require_once 'Core/GambioStorePackageInstaller.inc.php';
require_once 'Core/Exceptions/GambioStoreLanguageNotResolvableException.inc.php';
require_once 'Core/Exceptions/GambioStoreHttpErrorException.inc.php';
require_once 'Core/Exceptions/GambioStoreUpdatesNotRetrievableException.inc.php';

/**
 * Class GambioStoreConnector
 *
 * The entry point of the Gambio Store Connector, it takes care of package installations and removals.
 */
class GambioStoreConnector
{
    /**
     * @var \GambioStoreConfiguration
     */
    private $configuration;
    
    /**
     * @var \GambioStoreCompatibility
     */
    private $compatibility;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    /**
     * @var GambioStoreThemes
     */
    private $themes;
    
    /**
     * @var GambioStoreDatabase
     */
    private $database;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    /**
     * @var \GambioStoreShopInformation
     */
    private $shopInformation;
    
    /**
     * @var \GambioStoreCache
     */
    private $cache;
    
    /**
     * @var \GambioStoreBackup
     */
    private $backup;
    
    /**
     * @var \GambioStorePackageInstaller
     */
    private $installer;
    
    /**
     * @var \GambioStoreHttp
     */
    private $http;
    
    
    /**
     * GambioStoreConnector constructor.
     *
     * @param \GambioStoreDatabase         $database
     * @param \GambioStoreConfiguration    $configuration
     * @param \GambioStoreCompatibility    $compatibility
     * @param \GambioStoreLogger           $logger
     * @param \GambioStoreThemes           $themes
     * @param \GambioStoreFileSystem       $fileSystem
     * @param \GambioStoreShopInformation  $shopInformation
     * @param \GambioStoreCache            $cache
     * @param \GambioStoreHttp             $http
     * @param \GambioStoreBackup           $backup
     * @param \GambioStorePackageInstaller $installer
     */
    private function __construct(
        GambioStoreDatabase $database,
        GambioStoreConfiguration $configuration,
        GambioStoreCompatibility $compatibility,
        GambioStoreLogger $logger,
        GambioStoreThemes $themes,
        GambioStoreFileSystem $fileSystem,
        GambioStoreShopInformation $shopInformation,
        GambioStoreCache $cache,
        GambioStoreHttp $http,
        GambioStoreBackup $backup,
        GambioStorePackageInstaller $installer
    ) {
        $this->database        = $database;
        $this->configuration   = $configuration;
        $this->compatibility   = $compatibility;
        $this->logger          = $logger;
        $this->themes          = $themes;
        $this->fileSystem      = $fileSystem;
        $this->shopInformation = $shopInformation;
        $this->cache           = $cache;
        $this->http           = $http;
        $this->backup          = $backup;
        $this->installer       = $installer;
    }
    
    
    /**
     * Instantiates the GambioStoreConnector with its dependencies
     *
     * @return \GambioStoreConnector
     */
    public static function getInstance()
    {
        $fileSystem      = new GambioStoreFileSystem();
        $database        = GambioStoreDatabase::connect($fileSystem);
        $compatibility   = new GambioStoreCompatibility($database);
        $configuration   = new GambioStoreConfiguration($database, $compatibility);
        $shopInformation = new GambioStoreShopInformation($database, $fileSystem);
        $cache           = new GambioStoreCache($database);
        $http            = new GambioStoreHttp();
        $backup          = new GambioStoreBackup($fileSystem);
        $logger          = new GambioStoreLogger($cache);
        $themes          = new GambioStoreThemes($compatibility, $fileSystem, $logger);
        $installer       = new GambioStorePackageInstaller($fileSystem, $configuration, $cache, $logger, $backup, $themes, $compatibility);
        
        return new self($database, $configuration, $compatibility, $logger, $themes, $fileSystem, $shopInformation,
            $cache, $http, $backup, $installer);
    }
    
    
    /**
     * Returns the configuration wrapper
     *
     * @return \GambioStoreConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
    
    
    /**
     * Returns the compatibility checker
     *
     * @return \GambioStoreCompatibility
     */
    public function getCompatibility()
    {
        return $this->compatibility;
    }
    
    
    /**
     * Returns the class responsible for all themes actions in the shop
     *
     * @return \GambioStoreThemes
     */
    public function getThemes()
    {
        return $this->themes;
    }
    
    
    /**
     * Returns the GambioStoreDatabase instance.
     *
     * @return \GambioStoreDatabase
     */
    public function getDatabase()
    {
        return $this->database;
    }
    
    
    /**
     * Returns a GambioStoreFileSystem instance.
     *
     * @return \GambioStoreFileSystem
     */
    public function getFileSystem()
    {
        return $this->fileSystem;
    }
    
    
    /**
     * Returns a GambioStoreCache instance.
     *
     * @return \GambioStoreCache
     */
    public function getCache()
    {
        return $this->cache;
    }
    
    
    /**
     * Returns a GambioStoreLogger instance.
     *
     * @return \GambioStoreLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
    
    
    /**
     * Determines whether this shop send the store token for registration
     *
     * @param string $storeToken
     *
     * @return bool
     */
    public function verifyRegistration($storeToken)
    {
        $result = $this->configuration->get('GAMBIO_STORE_TOKEN') === $storeToken;
        if ($result) {
            $this->logger->notice('Registration verification succeed');
            $this->configuration->set('GAMBIO_STORE_IS_REGISTERED', 'true');
        } else {
            $this->logger->error('Registration verification failed');
        }
        
        return $result;
    }
    
    
    /**
     * Generates the Gambio Store Token for the Shop
     *
     * @return string
     */
    public function generateToken()
    {
        $prefix    = 'STORE';
        $date      = date('Ymd');
        $hash      = md5(time());
        $suffix    = 'XX';
        $delimiter = '-';
        
        return implode($delimiter, [$prefix, $date, $hash, $suffix]);
    }
    
    
    /**
     * Synthesizes the shop information and returns it as an array
     *
     * @return array
     */
    public function getShopInformation()
    {
        try {
            return $this->shopInformation->getShopInformation();
        } catch (\GambioStoreException $exception) {
            $this->logger->critical('Could not collected shop information', [
                'context' => $exception->getContext(),
                'error'   => [
                    'code'    => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine()
                ]
            ]);
            
            return [
                'error' => $exception->getMessage()
            ];
        }
    }
    
    
    /**
     * Determines whether a theme is active or not
     *
     * @param $themeName
     *
     * @return bool
     */
    public function isThemeActive($themeName)
    {
        $active = $this->themes->isActive($themeName);
        
        if ($active) {
            $this->logger->notice('The theme: ' . $themeName . ' is currently active');
        } else {
            $this->logger->notice('The theme: ' . $themeName . ' is currently inactive');
        }
        
        return $active;
    }
    
    
    /**
     * Returns the current shop language code.
     *
     * @return string
     * @throws \GambioStoreLanguageNotResolvableException
     */
    public function getCurrentShopLanguageCode()
    {
        if (isset($_SESSION['languages_id'])) {
            $rows = $this->database->query('SELECT `code` FROM `languages` WHERE `languages_id` = :id', [
                'id' => $_SESSION['languages_id']
            ]);
            $id   = $rows->fetchColumn();
            if ($id !== false) {
                return $id;
            }
        }
        
        if (defined('DEFAULT_LANGUAGE')) {
            return DEFAULT_LANGUAGE;
        }
        
        throw new GambioStoreLanguageNotResolvableException(['languages_id' => $_SESSION['languages_id']]);
    }
    
    
    /**
     * Installs a package.
     *
     * @param $packageData
     *
     * @return bool[]
     * @throws \Exception
     */
    public function installPackage($packageData)
    {
        return $this->installer->installPackage($packageData);
    }
    
    
    /**
     * Uninstalls a package.
     *
     * @param array $postData
     *
     * @return bool[]
     * @throws \Exception
     */
    public function uninstallPackage(array $postData)
    {
        return $this->installer->uninstallPackage($postData);
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
    public function fetchAvailableUpdates()
    {
        if (!extension_loaded('curl') || !$this->isAllowedToGetUpdates()) {
            return [];
        }
        
        try {
            $shopInformationArray = $this->shopInformation->getShopInformation();
            $storeToken           = $this->configuration->get('GAMBIO_STORE_TOKEN');
            $apiUrl               = $this->getGambioStoreApiUrl();
            $response             = $this->http->post($apiUrl . '/connector/updates',
                json_encode(['shopInformation' => $shopInformationArray]), [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/json',
                        'X-STORE-TOKEN: ' . $storeToken
                    ]
                ]);
            
            $response = json_decode($response->getBody(), true);
        } catch (GambioStoreHttpErrorException $exception) {
            $message = 'Network failure while trying to fetch updates.';
            $this->logger->error($message, [
                'context' => $exception->getContext(),
                'error'   => [
                    'code'    => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine()
                ],
            ]);
            throw new GambioStoreUpdatesNotRetrievableException($message, $exception->getCode(),
                $exception->getContext(), $exception);
        } catch (GambioStoreException $exception) {
            $message = 'Could not fetch shop information during update-fetching!';
            $this->logger->error($message, [
                'context' => $exception->getContext(),
                'error'   => [
                    'code'    => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine()
                ],
            ]);
            throw new GambioStoreUpdatesNotRetrievableException($message, $exception->getCode(),
                $exception->getContext(), $exception);
        }
        
        if (!is_array($response) || !array_key_exists('updates', $response)
            || !is_array($response['updates'])) {
            throw new GambioStoreUpdatesNotRetrievableException("The API returned invalid updates!", 0,
                ['response' => $response]);
        }
        
        return $response['updates'];
    }
    
    /***
     * Checks if shop is allowed to get updates.
     * Note this returns false if :
     *  - Gambio Store is not registered
     *  - Gambio Store data processing is not accepted
     *  - Gambio token is not existing
     *
     * @return bool
     */
    private function isAllowedToGetUpdates()
    {
        if (!$this->configuration->get('GAMBIO_STORE_IS_REGISTERED') === true) {
            return false;
        }
        
        if (!$this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') === true) {
            return false;
        }
        
        if (!$this->configuration->get('GAMBIO_STORE_TOKEN') === true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Gets the store api URL
     *
     * @return string
     */
    private function getGambioStoreApiUrl()
    {
        $gambioUrl = $this->configuration->get('GAMBIO_STORE_API_URL');
        
        // Fall back to the production Gambio Store api URL if none is set.
        if (empty($gambioUrl)) {
            $gambioUrl = 'https://store.gambio.com';
            $this->configuration->create('GAMBIO_STORE_API_URL', $gambioUrl);
        }
        
        return $gambioUrl;
    }
}

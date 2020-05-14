<?php
/* --------------------------------------------------------------
   GambioStoreConnector.php 2020-04-29
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
require_once 'Core/GambioStoreShopInformation.php';

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
     * @var \GambioStoreShopInformation
     */
    private $shopInformation;
    
    
    /**
     * GambioStoreConnector constructor.
     *
     * @param \GambioStoreDatabase        $database
     * @param \GambioStoreConfiguration   $configuration
     * @param \GambioStoreCompatibility   $compatibility
     * @param \GambioStoreLogger          $logger
     * @param \GambioStoreThemes          $themes
     * @param \GambioStoreShopInformation $shopInformation
     */
    private function __construct(
        GambioStoreDatabase $database,
        GambioStoreConfiguration $configuration,
        GambioStoreCompatibility $compatibility,
        GambioStoreLogger $logger,
        GambioStoreThemes $themes,
        GambioStoreShopInformation $shopInformation
    ) {
        $this->database        = $database;
        $this->configuration   = $configuration;
        $this->compatibility   = $compatibility;
        $this->logger          = $logger;
        $this->themes          = $themes;
        $this->shopInformation = $shopInformation;
    }
    
    
    /**
     * Instantiates the GambioStoreConnector with its dependencies
     *
     * @return \GambioStoreConnector
     */
    public static function getInstance()
    {
        $database        = GambioStoreDatabase::connect();
        $compatibility   = new GambioStoreCompatibility($database);
        $configuration   = new GambioStoreConfiguration($database, $compatibility);
        $logger          = new GambioStoreLogger();
        $themes          = new GambioStoreThemes($compatibility);
        $shopInformation = new GambioStoreShopInformation($database);
    
        return new self($database, $configuration, $compatibility, $logger, $themes, $shopInformation);
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
     * Determines whether this shop send the store token for registration
     *
     * @param $storeToken
     *
     * @return bool
     */
    public function verifyRegistration($storeToken)
    {
        $result = $this->configuration->get('GAMBIO_STORE_TOKEN') === $storeToken;
        if ($result) {
            $this->configuration->set('GAMBIO_STORE_IS_REGISTERED', 'true');
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
        } catch (\GambioStoreException $e) {
            $this->logger->critical($e->getMessage(), $e->getContext());
    
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    
    /**
     * Determines whether a theme is active or not
     *
     * @param $themeName
     */
    public function isThemeActive($themeName)
    {
        return $this->themes->isActive($themeName);
    }
    
    
    /**
     * Returns the GambioStoreDatabase
     *
     * @return \GambioStoreDatabase
     */
    public function getDatabase()
    {
        return $this->database;
    }
    
    
    /**
     * Returns the current shop language code.
     *
     * @return string
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
        
        return 'en';
    }
    
    
    /**
     * Installs a package
     *
     * @param $packageData
     *
     * @return bool[]
     * @throws \PackageInstallationException
     */
    public function installPackage($packageData)
    {
        $cache       = new GambioStoreCache(GambioStoreDatabase::connect());
        $installaton = new GambioStoreInstallation($packageData, $this->configuration->get('GAMBIO_STORE_TOKEN'),
            $cache, $this->logger);
        
        return $installaton->perform();
    }
}

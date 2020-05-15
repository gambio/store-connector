<?php
/* --------------------------------------------------------------
   GambioStoreConnector.php 2020-05-14
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
require_once 'Core/GambioStoreShopInformation.php';
require_once __DIR__ . '/Core/Exceptions/GambioStoreLanguageNotResolvableException.inc.php';

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
     * @var \GambioStoreUpdater
     */
    private $updater;
    
    
    /**
     * GambioStoreConnector constructor.
     *
     * @param \GambioStoreDatabase        $database
     * @param \GambioStoreConfiguration   $configuration
     * @param \GambioStoreCompatibility   $compatibility
     * @param \GambioStoreLogger          $logger
     * @param \GambioStoreThemes          $themes
     * @param \GambioStoreFileSystem      $fileSystem
     * @param \GambioStoreShopInformation $shopInformation
     */
    private function __construct(
        GambioStoreDatabase $database,
        GambioStoreConfiguration $configuration,
        GambioStoreCompatibility $compatibility,
        GambioStoreLogger $logger,
        GambioStoreThemes $themes,
        GambioStoreFileSystem $fileSystem,
        GambioStoreShopInformation $shopInformation,
        GambioStoreUpdater $updater
    ) {
        $this->database        = $database;
        $this->configuration   = $configuration;
        $this->compatibility   = $compatibility;
        $this->logger          = $logger;
        $this->themes          = $themes;
        $this->fileSystem      = $fileSystem;
        $this->shopInformation = $shopInformation;
        $this->updater         = $updater;
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
        $logger          = new GambioStoreLogger();
        $themes          = new GambioStoreThemes($compatibility, $fileSystem);
        $shopInformation = new GambioStoreShopInformation($database, $fileSystem);
        $updater         = new GambioStoreUpdater($configuration, $database, $fileSystem);
    
        return new self($database, $configuration, $compatibility, $logger, $themes, $fileSystem, $shopInformation,
            $updater);
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
     * Returns the GambioStoreDatabase instance.
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
        
        throw new GambioStoreLanguageNotResolvableException(['languages_id' => $_SESSION['languages_id']]);
    }
    
    
    /**
     * Installs a package.
     *
     * @param $packageData
     *
     * @return bool[]
     * @throws \PackageInstallationException
     */
    public function installPackage($packageData)
    {
        $cache       = new GambioStoreCache(GambioStoreDatabase::connect($this->getFileSystem()));
        $installaton = new GambioStoreInstallation($packageData, $this->configuration->get('GAMBIO_STORE_TOKEN'),
            $cache, $this->logger);
        
        return $installaton->perform();
    }
    
    
    /**
     * Uninstalls a package.
     *
     * @param $fileLIst
     *
     * @return bool[]
     * @throws \PackageInstallationException
     */
    public function uninstallPackage($fileLIst)
    {
        $cache   = new GambioStoreCache(GambioStoreDatabase::connect());
        $removal = new GambioStoreRemoval($fileLIst);
        
        return $removal->perform();
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
     * Updates the GambioStoreConnector
     */
    public function update()
    {
        $this->updater->update();
    }
}

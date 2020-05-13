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
     * GambioStoreConnector constructor.
     *
     * @param \GambioStoreDatabase      $database
     * @param \GambioStoreConfiguration $configuration
     * @param \GambioStoreCompatibility $compatibility
     * @param \GambioStoreLogger        $logger
     * @param \GambioStoreThemes        $themes
     */
    private function __construct(
        GambioStoreDatabase $database,
        GambioStoreConfiguration $configuration,
        GambioStoreCompatibility $compatibility,
        GambioStoreLogger $logger,
        GambioStoreThemes $themes
    ) {
        $this->database      = $database;
        $this->configuration = $configuration;
        $this->compatibility = $compatibility;
        $this->logger        = $logger;
        $this->themes        = $themes;
    }
    
    
    /**
     * Instantiates the GambioStoreConnector with its dependencies
     *
     * @return \GambioStoreConnector
     */
    public static function getInstance()
    {
        $database      = GambioStoreDatabase::connect();
        $compatibility = new GambioStoreCompatibility($database);
        $configuration = new GambioStoreConfiguration($database, $compatibility);
        $logger        = new GambioStoreLogger();
        $themes        = new GambioStoreThemes($compatibility);
    
        return new self($database, $configuration, $compatibility, $logger, $themes);
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
     * Returns the GambioStoreDatabase
     *
     * @return \GambioStoreDatabase
     */
    public function getDatabase()
    {
        return $this->database;
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

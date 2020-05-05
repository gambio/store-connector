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
     * GambioStoreConnector constructor.
     *
     * @param \GambioStoreConfiguration $configuration
     * @param \GambioStoreCompatibility $compatibility
     * @param \GambioStoreLogger        $logger
     */
    private function __construct(
        GambioStoreConfiguration $configuration,
        GambioStoreCompatibility $compatibility,
        GambioStoreLogger $logger
    ) {
        $this->configuration = $configuration;
        $this->compatibility = $compatibility;
        $this->logger        = $logger;
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
        
        return new self($configuration, $compatibility, $logger);
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
}

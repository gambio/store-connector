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
    public function __construct(
        GambioStoreConfiguration $configuration,
        GambioStoreCompatibility $compatibility,
        GambioStoreLogger $logger
    ) {
        $this->configuration = $configuration;
        $this->compatibility = $compatibility;
        $this->logger        = $logger;
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
     * Determines whether the storeToken belongs to the shop or not
     *
     * @param $storeToken
     *
     * @return bool
     */
    public function verifyStoreToken($storeToken)
    {
        return $this->configuration->get('GAMBIO_STORE_TOKEN') === $storeToken;
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

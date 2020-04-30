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

require __DIR__ . 'Core/GambioStoreCompatibility.inc.php';
require __DIR__ . 'Core/GambioStoreDatabase.inc.php';
require __DIR__ . 'Core/GambioStoreLogger.inc.php';
require __DIR__ . 'Core/GambioStoreConfiguration.inc.php';

/**
 * Class GambioStoreConnector
 *
 * The entry point of the Gambio Store Connector, it takes care of package installations and removals.
 */
class GambioStoreConnector
{
    
    /**
     * @var \inc
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
     * @return \GambioStoreConnector
     */
    public static function getInstance()
    {
        $database      = GambioStoreDatabase::connect();
        $compatability = new GambioStoreCompatibility($database);
        $configuration = new GambioStoreConfiguration($database, $compatability);
        $logger        = new GambioStoreLogger();
        
        return new self($configuration, $compatability, $logger);
    }
    
    
    /**
     * @return \GambioStoreConfiguration|\inc
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
    
    public function getCompatibility()
    {
        return $this->compatibility;
    }
}

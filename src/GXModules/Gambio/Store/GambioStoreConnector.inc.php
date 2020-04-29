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

require __DIR__ . '/Core/GambioStoreCompatibility.inc.php';
require __DIR__ . '/Core/GambioStoreDatabase.inc.php';
require __DIR__ . '/Core/GambioStoreLogger.inc.php';

/**
 * Class StoreModule
 *
 * The entry point of the Store Connector, it takes care of package installations and removals.
 */
class GambioStoreConnector
{
    
    /**
     * @var \StoreConfiguration
     */
    private $configuration;
    
    /**
     * @var \StoreCompatibility
     */
    private $compatibility;
    
    /**
     * @var \StoreLogger
     */
    private $logger;
    
    
    /**
     * GambioStoreConnector constructor.
     *
     * @param \StoreConfiguration $configuration
     * @param \StoreCompatibility $compatibility
     * @param \StoreLogger        $logger
     */
    public function __construct(
        StoreConfiguration $configuration,
        StoreCompatibility $compatibility,
        StoreLogger $logger
    ) {
        $this->configuration = $configuration;
        $this->compatibility = $compatibility;
        $this->logger        = $logger;
    }
    
    
    public function run()
    {
        // TODO: Process the incoming request.     
    }
}

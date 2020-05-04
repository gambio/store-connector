<?php
/* --------------------------------------------------------------
   GambioStoreConnectorFactory.php 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

require_once 'GambioStoreConnector.inc.php';
require_once 'Core/GambioStoreCompatibility.inc.php';
require_once 'Core/GambioStoreDatabase.inc.php';
require_once 'Core/GambioStoreLogger.inc.php';
require_once 'Core/GambioStoreConfiguration.inc.php';

class GambioStoreConnectorFactory
{
    /**
     * Instantiates the GambioStoreConnector with its dependencies
     *
     * @return \GambioStoreConnector
     */
    public static function createConnector()
    {
        $database      = GambioStoreDatabase::connect();
        $compatibility = new GambioStoreCompatibility($database);
        $configuration = new GambioStoreConfiguration($database, $compatibility);
        $logger        = new GambioStoreLogger();
        
        return new GambioStoreConnector($configuration, $compatibility, $logger);
    }
}

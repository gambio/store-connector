<?php
/* --------------------------------------------------------------
   GambioStoreConnectorFactory.php 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

require __DIR__ . '/GambioStoreConnector.inc.php';
require __DIR__ . 'Core/GambioStoreCompatibility.inc.php';
require __DIR__ . 'Core/GambioStoreDatabase.inc.php';
require __DIR__ . 'Core/GambioStoreLogger.inc.php';
require __DIR__ . 'Core/GambioStoreConfiguration.inc.php';

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

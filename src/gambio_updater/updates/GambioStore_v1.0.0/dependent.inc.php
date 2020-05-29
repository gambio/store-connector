<?php
define('StoreKey_MigrationScript', true);

require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreFileSystemFacade.php';
require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreCompatibilityFacade.php';
require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreDatabaseFacade.php';
require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreConfigurationFacade.php';
require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreCacheFacade.php';
require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreHttpFacade.php';
require __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreLoggerFacade.php';

$fileSystem    = new GambioStoreFileSystemFacade();
$database      = GambioStoreDatabaseFacade::connect($fileSystem);
$compatibility = new GambioStoreCompatibilityFacade($database);
$configuration = new GambioStoreConfigurationFacade($database, $compatibility);
$cache         = new GambioStoreCacheFacade($database);
$http          = new GambioStoreHttpFacade();
$log           = new GambioStoreLoggerFacade($cache);

require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/update.php';
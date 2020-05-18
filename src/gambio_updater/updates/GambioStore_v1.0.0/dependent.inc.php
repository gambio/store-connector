<?php
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreFileSystemFacade.php';
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreCompatibilityFacade.php';
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreDatabaseFacade.php';
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreConfigurationFacade.php';
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreCacheFacade.php';
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreHttpFacade.php';
require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/Facades/GambioStoreLoggerFacade.php';

$fileSystem    = new GambioStoreFileSystemFacade();
$database      = GambioStoreDatabaseFacade::connect($fileSystem);
$compatibility = new GambioStoreCompatibilityFacade($database);
$configuration = new GambioStoreConfigurationFacade($database, $compatibility);

require_once __DIR__ . '/../../../GXModules/Gambio/Store/Core/update.php';
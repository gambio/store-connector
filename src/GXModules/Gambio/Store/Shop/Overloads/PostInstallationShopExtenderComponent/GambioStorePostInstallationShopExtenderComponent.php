<?php
/* --------------------------------------------------------------
   StorePostInstallationShopExtenderComponent.inc.php 2021-05-06
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2019 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStorePostInstallationShopExtenderComponent
 * The class is there so that when you install the shop with a store connector, the first update of the Gambio Store is also carried out.
 */
class GambioStorePostInstallationShopExtenderComponent extends GambioStorePostInstallationShopExtenderComponent_parent
{
    public function proceed()
    {
        parent::proceed();
    
        define('StoreKey_MigrationScript', true);
    
        require __DIR__ . '/../../../Core/Facades/GambioStoreFileSystemFacade.php';
        require __DIR__ . '/../../../Core/Facades/GambioStoreCompatibilityFacade.php';
        require __DIR__ . '/../../../Core/Facades/GambioStoreDatabaseFacade.php';
        require __DIR__ . '/../../../Core/Facades/GambioStoreConfigurationFacade.php';
        require __DIR__ . '/../../../Core/Facades/GambioStoreCacheFacade.php';
        require __DIR__ . '/../../../Core/Facades/GambioStoreHttpFacade.php';
        require __DIR__ . '/../../../Core/Facades/GambioStoreLoggerFacade.php';
    
        $fileSystem    = new GambioStoreFileSystemFacade();
        $database      = GambioStoreDatabaseFacade::connect($fileSystem);
        $compatibility = new GambioStoreCompatibilityFacade($database);
        $configuration = new GambioStoreConfigurationFacade($database, $compatibility);
        $cache         = new GambioStoreCacheFacade($database);
        $http          = new GambioStoreHttpFacade();
        $log           = new GambioStoreLoggerFacade($cache);
    
        require_once __DIR__ . '/../../../Core/update.php';
    }
}

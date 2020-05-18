<?php
/* --------------------------------------------------------------
   GambioStoreMigration.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStoreMigration
 *
 * This class migrates up and down for the gambio store.
 */
class GambioStoreMigration
{
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    /**
     * @var array
     */
    private $up;
    
    /**
     * @var array
     */
    private $down;
    
    
    /**
     * GambioStoreMigration constructor.
     *
     * @param \GambioStoreFileSystem $fileSystem
     * @param array                  $up
     * @param array                  $down
     */
    public function __construct(\GambioStoreFileSystem $fileSystem, array $up, array $down)
    {
        $this->fileSystem = $fileSystem;
        $this->up         = $up;
        $this->down       = $down;
    }
    
    
    /**
     * Migrate up.
     *
     * @throws \Exception
     */
    public function up()
    {
        /**
         * Require and instantiate all facades
         */
        $this->requireFacades();
    
        $fileSystem    = new GambioStoreFileSystemFacade();
        $database      = GambioStoreDatabaseFacade::connect($fileSystem);
        $compatibility = new GambioStoreCompatibilityFacade($database);
        $configuration = new GambioStoreConfigurationFacade($database, $compatibility);
        $cache         = new GambioStoreCacheFacade($database);
        $http          = new GambioStoreHttpFacade();
        $log           = new GambioStoreLoggerFacade();
    
        foreach ($this->up as $item) {
            try {
                require_once $this->fileSystem->getShopDirectory() . '/' . $item;
            } catch (\Exception $exception) {
                throw new UpMigrationException('Up migration failed. File: ', 0, $item);
            }
        }
    }
    
    
    /**
     * Migrate down.
     *
     * @throws \Exception
     */
    public function down()
    {
        /**
         * Require and instantiate all facades
         */
        $this->requireFacades();
    
        $fileSystem    = new GambioStoreFileSystemFacade();
        $database      = GambioStoreDatabaseFacade::connect($fileSystem);
        $compatibility = new GambioStoreCompatibilityFacade($database);
        $configuration = new GambioStoreConfigurationFacade($database, $compatibility);
        $cache         = new GambioStoreCacheFacade($database);
        $http          = new GambioStoreHttpFacade();
        $log           = new GambioStoreLoggerFacade();
    
        foreach ($this->down as $item) {
            try {
                require_once $this->fileSystem->getShopDirectory() . '/' . $item;
            } catch (\Exception $exception) {
                throw new DownMigrationException('Down migration failed. File: ', 0, $item);
            }
        }
    }
    
    
    /**
     * Requires all facades
     */
    private function requireFacades()
    {
        require_once 'Facades/GambioStoreFileSystemFacade.php';
        require_once 'Facades/GambioStoreCompatibilityFacade.php';
        require_once 'Facades/GambioStoreDatabaseFacade.php';
        require_once 'Facades/GambioStoreConfigurationFacade.php';
        require_once 'Facades/GambioStoreCacheFacade.php';
        require_once 'Facades/GambioStoreHttpFacade.php';
        require_once 'Facades/GambioStoreLoggerFacade.php';
    }
}
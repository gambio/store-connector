<?php
/* --------------------------------------------------------------
   GambioStoreRemoval.inc.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
require_once 'Exceptions/GambioStoreRemovalException.inc.php';

/**
 * Class GambioStoreRemoval
 *
 * Performs a Store package removal and take care of all the required actions.
 *
 * Execute the downgrade script if needed.
 */
class GambioStoreRemoval
{
    /**
     * @var array
     */
    private $packageData;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    /**
     * @var \GambioStoreBackup
     */
    private $backup;
    
    /**
     * @var \GambioStoreMigration
     */
    private $migration;
    
    
    /**
     * GambioStoreRemoval constructor.
     *
     * @param array                 $packageData
     * @param \GambioStoreLogger    $logger
     * @param \GambioStoreBackup    $backup
     * @param \GambioStoreMigration $migration
     */
    public function __construct(
        array $packageData,
        GambioStoreLogger $logger,
        GambioStoreBackup $backup,
        GambioStoreMigration $migration
    ) {
        $this->packageData = $packageData;
        $this->logger      = $logger;
        $this->backup      = $backup;
        $this->migration   = $migration;
        
        register_shutdown_function([$this, 'shutdownCallback']);
    }
    
    
    /**
     * Starts the performing of a gambio store removal.
     * Removes for example the files of a gambio store package.
     *
     * @return bool[]
     * @throws \Exception
     * @throws \GambioStoreRemovalException
     */
    public function perform()
    {
        $files = $this->packageData['files_list'];
        $name  = $this->packageData['name'];
        
        try {
            $this->backup->movePackageFilesToCache($files);
            $this->migration->down();
            $this->backup->removePackageFilesFromCache($files);
        } catch (Exception $exception) {
            $message = 'Could not remove package: ' . $name;
            $this->logger->error($message, ['package' => $this->packageData, 'error' => $exception]);
            $this->backup->restorePackageFilesFromCache($files);
            throw new GambioStoreRemovalException($message);
        }
        
        $this->logger->notice('Successfully removed package: ' . $name);
        
        return ['success' => true];
    }
    
    
    /**
     * Shutdown callback function.
     *
     * @throws \Exception
     */
    public function shutdownCallback()
    {
        $error = error_get_last();
        if ($error) {
            $this->logger->critical('Critical error during package removal from package: ' . $this->packageData['name'],
                ['package' => $this->packageData, 'error' => $error]);
            $this->backup->restorePackageFilesFromCache($this->packageData['files_list']);
        }
    }
}

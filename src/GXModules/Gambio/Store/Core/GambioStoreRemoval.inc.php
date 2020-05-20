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
    private $fileList;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    /**
     * @var \GambioStoreBackup
     */
    private $backup;
    
    
    /**
     * GambioStoreRemoval constructor.
     *
     * @param array              $fileList
     * @param \GambioStoreLogger $logger
     * @param \GambioStoreBackup $backup
     */
    public function __construct(
        array $fileList,
        GambioStoreLogger $logger,
        GambioStoreBackup $backup
    ) {
        $this->fileList = $fileList;
        $this->logger   = $logger;
        $this->backup   = $backup;
        
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
        try {
            $this->logger->notice('Try to remove package', ['fileList' => $this->fileList]);
            $this->logger->info('Start by move all files to cache directory');
            $this->backup->movePackageFilesToCache($this->fileList);
            $this->logger->info('Removing backup files form cache');
            // TODO: call clear cache from backup class if implemented $this->backup->removePackageFilesFromCache()
        } catch (Exception $exception) {
            $message = 'Could not remove package';
            $this->logger->error($message, ['error' => $exception]);
            $this->backup->restorePackageFilesFromCache($this->fileList);
            throw new GambioStoreRemovalException($message);
        }
        
        $this->logger->info('succeed');
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
            $this->logger->critical('Critical error during package removal', ['error' => $error]);
            $this->backup->restorePackageFilesFromCache($this->fileList);
        }
    }
}

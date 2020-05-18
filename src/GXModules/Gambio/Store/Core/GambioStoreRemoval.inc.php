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
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
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
     * @param array                  $fileList
     * @param \GambioStoreLogger     $logger
     * @param \GambioStoreFileSystem $fileSystem
     * @param \GambioStoreBackup     $backup
     */
    public function __construct(
        array $fileList,
        GambioStoreLogger $logger,
        GambioStoreFileSystem $fileSystem,
        GambioStoreBackup $backup
    ) {
        $this->fileList   = $fileList;
        $this->fileSystem = $fileSystem;
        $this->logger     = $logger;
        $this->backup     = $backup;
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
        register_shutdown_function([$this, 'shutdownCallback']);
        
        try {
            $this->backup->backupPackageFiles($this->fileList);
            $this->removeFiles();
        } catch (Exception $exception) {
            $this->logger->error('Could not remove package',
                ['fileList' => $this->fileList, 'exception' => $exception]);
            $this->backup->restoreBackUp($this->fileList);
            throw new GambioStoreRemovalException('Could not remove package');
        }
        
        return ['success' => true];
    }
    
    
    /**
     * Removes all files or directory form package file list.
     */
    private function removeFiles()
    {
        foreach ($this->fileList as $file) {
            $this->fileSystem->remove($file);
        }
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
            $this->backup->restoreBackUp($this->fileList);
        }
    }
}

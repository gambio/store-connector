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
require_once "GambioStoreLogger.inc.php";
require_once "GambioStoreBackup.inc.php";
require_once "GambioStoreMigration.inc.php";
require_once "GambioStoreFileSystem.inc.php";
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
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    
    /**
     * GambioStoreRemoval constructor.
     *
     * @param array                  $packageData
     * @param \GambioStoreLogger     $logger
     * @param \GambioStoreBackup     $backup
     * @param \GambioStoreMigration  $migration
     * @param \GambioStoreFileSystem $fileSystem
     */
    public function __construct(
        array $packageData,
        GambioStoreLogger $logger,
        GambioStoreBackup $backup,
        GambioStoreMigration $migration,
        GambioStoreFileSystem $fileSystem
    ) {
        $this->packageData = $packageData;
        $this->logger      = $logger;
        $this->backup      = $backup;
        $this->migration   = $migration;
        $this->fileSystem  = $fileSystem;
        
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
            $this->removeEmptyFolders($files);
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
    
    
    /**
     * Remove all empty folders inside themes and GXModules related to this package
     *
     * @param $files
     */
    private function removeEmptyFolders($files)
    {
        // We'll only remove folders inside themes and GXModules
        $foldersOfInterest = array_filter($files, function ($value) {
            return strpos($value, 'themes/') === 0 || strpos($value, 'GXModules/');
        });
        
        // Lets make sure we only have folders
        array_walk($foldersOfInterest, function (&$item) {
            if (!is_dir($item)) {
                $item = dirname($item);
            }
        });
        
        $foldersOfInterest = array_unique($foldersOfInterest);
        
        // Sort based on length to delete deepest folders first
        usort($foldersOfInterest, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($foldersOfInterest as $foldersToCheck) {
            $this->removeEmptyFoldersRecursively($foldersToCheck);
        }
    }
    
    
    /**
     * Recursively delete each empty folder until either a folder is not empty or we reached themes or GXModules
     *
     * @param $path
     */
    private function removeEmptyFoldersRecursively($path)
    {
        if (!is_dir($path)) {
            $path = dirname($path);
        }
        
        if ($path === 'themes'
            || $path === 'GXModules'
            || !$this->isFolderEmpty($path)) {
            return;
        }
        
        $this->fileSystem->remove($path);
        $this->removeEmptyFoldersRecursively(dirname($path));
    }
    
    
    /**
     * Check if a folder is empty
     *
     * @param $folder
     *
     * @return bool
     */
    private function isFolderEmpty($folder)
    {
        $path = $this->fileSystem->getShopDirectory() . '/' . $folder;
        
        return count(scandir($path)) === 2;
    }
}

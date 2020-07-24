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
 * The class performs a package removal in the shop. The class receives the packages files to remove.
 *
 * The main logic is in the `perform` method. It moves the package files to the backup folder to simulate deletion.
 * This allows us to restore the package files if an error occurs. Afterwards empty directories are removed.
 * Lastly the package migrations are run. If this step was successful, the backup is removed and the package removal
 * completed. If at any point an error occurs, the package will be restored from the backup.
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
        
        set_error_handler([$this, 'handleUnexpectedError']);
        set_exception_handler([$this, 'handleUnexpectedException']);
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
            $this->logger->error($message, [
                'error' => [
                    'code'    => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine()
                ]
            ]);
            $this->backup->restorePackageFilesFromCache($files);
            throw new GambioStoreRemovalException($message);
        }
        
        $this->logger->notice('Successfully removed package: ' . $name);
        
        return ['success' => true];
    }
    
    
    /**
     * Error handler function.
     *
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     *
     * @throws \Exception
     */
    public function handleUnexpectedError($code, $message, $file, $line)
    {
        if ($code === E_USER_ERROR) {
            $this->logger->critical('Critical error during package removal from package: ' . $this->packageData['name'],
                [
                    'error' => [
                        'code'    => $code,
                        'message' => $message,
                        'file'    => $file,
                        'line'    => $line
                    ]
                ]);
            $this->backup->restorePackageFilesFromCache($this->packageData['files_list']);
            die();
        }
        
        if ($code !== 2) {
            $this->logger->warning('Minor error during package removal from package: ' . $this->packageData['name'], [
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                    'file'    => $file,
                    'line'    => $line
                ]
            ]);
        }
    }
    
    
    /**
     * Exception handler function.
     *
     * @param $exception
     *
     * @throws \Exception
     */
    public function handleUnexpectedException(Exception $exception)
    {
        $this->logger->critical('Critical error during package removal from package: ' . $this->packageData['name'], [
            'error' => [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine()
            ]
        ]);
        $this->backup->restorePackageFilesFromCache($this->packageData['files_list']);
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
            return strpos($value, 'themes/') === 0
                   || strpos($value, 'GXModules/') === 0;
        });
        
        // Lets make sure we only have folders
        array_walk($foldersOfInterest, function (&$item) {
            if (!is_dir($this->fileSystem->getShopDirectory() . '/' . $item)) {
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
        
        return @count(@scandir($path)) === 2;
    }
}

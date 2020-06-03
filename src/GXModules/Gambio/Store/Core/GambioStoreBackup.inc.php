<?php
/* --------------------------------------------------------------
   GambioStoreBackup.inc.php 2020-05-14
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'GambioStoreFileSystem.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreFileNotFoundException.inc.php';

class GambioStoreBackup
{
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    
    /**
     * GambioStoreBackup constructor.
     *
     * @param \GambioStoreFileSystem $fileSystem
     */
    public function __construct(GambioStoreFileSystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }
    
    
    /**
     * Restores backup.
     *
     * @param array $toRestore
     *
     * @throws \Exception
     */
    public function restorePackageFilesFromCache(array $toRestore)
    {
        $filesToRemove = $this->getDifferenceBetweenBackupAndActualPackage($toRestore);
        foreach ($filesToRemove as $fileToRemove) {
            $this->fileSystem->remove($fileToRemove);
        }
        
        foreach ($toRestore as $file) {
            try {
                $this->fileSystem->move('cache/GambioStore/backup/' . $file . '.bak', $file);
            } catch (GambioStoreFileNotFoundException $e) {
                // If the file we're trying to move doesnt exist we can ignore it because it means
                // that the file didnt exist in the previous version of the package.
            }
        }
    }
    
    
    /**
     * Backups files.
     *
     * @param array $files
     *
     * @throws \Exception
     */
    public function movePackageFilesToCache(array $files)
    {
        foreach ($files as $file) {
            try {
                $this->fileSystem->move($file, 'cache/GambioStore/backup/' . $file . '.bak');
            } catch (GambioStoreFileNotFoundException $e) {
                // If the file we're trying to move doesnt exist we can ignore it because it means
                // that the file didnt exist in the previous version of the package.
            }
        }
    }
    
    
    /**
     * Removes package backup files from cache.
     *
     * @param array $files
     */
    public function removePackageFilesFromCache(array $files)
    {
        $cacheDirectory = 'cache/backup/';
        
        foreach ($files as $file) {
            $file .= '.bak';
    
            $this->fileSystem->remove($cacheDirectory . $file);
        }
    }
    
    
    /**
     * Returns an array of files are different between backup and actual package.
     *
     * @param array $files
     *
     * @return array
     */
    public function getDifferenceBetweenBackupAndActualPackage(array $files)
    {
        $difference = [];

        foreach($files as $file) {
            if (!is_file($this->fileSystem->getShopDirectory() . '/cache/GambioStore/backup/' . $file . '.bak')) {
                $difference[] = $file;
            }
        }
        
        return $difference;
    }
}

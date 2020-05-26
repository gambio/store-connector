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
        foreach ($toRestore as $file) {
            if (file_exists($this->fileSystem->getShopDirectory() . '/cache/backup/' . $file . '.bak')) {
                $this->fileSystem->move('cache/backup/' . $file . '.bak', $file);
            } elseif (file_exists($this->fileSystem->getShopDirectory() . '/' . $file)) {
                @unlink($this->fileSystem->getShopDirectory() . '/' . $file);
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
        $shopDirectory = $this->fileSystem->getShopDirectory() . '/';
        
        foreach ($files as $file) {
            if (strpos($file, $shopDirectory) === false) {
                $packageFileSource = $shopDirectory . $file;
            } else {
                $packageFileSource = $file;
                $file              = str_replace($shopDirectory, '', $file);
            }
            
            if (file_exists($packageFileSource) && is_file($packageFileSource)) {
                $this->fileSystem->move($file, 'cache/backup/' . $file . '.bak');
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
        $shopDirectory  = $this->fileSystem->getShopDirectory() . '/';
        $cacheDirectory = 'cache/backup/';
        
        foreach ($files as $file) {
            if (is_file($shopDirectory . $file . '.bak')) {
                $file .= '.bak';
            }
            
            if (strpos($file, $shopDirectory) === false) {
                $this->fileSystem->remove($cacheDirectory . $file);
            } else {
                $file = str_replace($shopDirectory, '', $file);
                $this->fileSystem->remove($cacheDirectory . $file);
            }
        }
    }
}

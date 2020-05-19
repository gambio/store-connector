<?php
/* --------------------------------------------------------------
   GambioStoreBackup.inc.php 2020-05-14
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
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
            if (file_exists($file) && is_file($file)) {
                $this->fileSystem->move($file, 'cache/backup/' . $file . '.bak');
            }
        }
    }
}

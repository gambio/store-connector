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
     * @param $toRestore
     *
     * @throws \Exception
     */
    public function restoreBackUp($toRestore)
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
     * @param $files
     *
     * @throws \Exception
     */
    public function backupPackageFiles($files)
    {
        foreach ($files as $file) {
            $packageFileSource = $this->fileSystem->getShopDirectory() . '/' . $file;
            
            if (file_exists($packageFileSource) && is_file($packageFileSource)) {
                $this->fileSystem->move($file, 'cache/backup/' . $file . '.bak');
            }
        }
    }
}

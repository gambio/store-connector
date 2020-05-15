<?php
/* --------------------------------------------------------------
   GambioStoreGambioStoreBackup.inc.php 2020-05-14
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

class GambioStoreGambioStoreBackup
{
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    
    private $packageId;
    
    
    public function __construct($packageId)
    {
        $this->packageId = $packageId;
        $this->fileSystem = new GambioStoreFileSystem();
    }
    
    
    public function restoreBackUp($toRestore)
    {
        foreach ($toRestore as $file) {
            if (file_exists($this->fileSystem->getShopDirectory() . '/cache/backup/' . $this->packageId . '/' . $file . '.bak')) {
                $this->fileSystem->move('cache/backup/' . $this->packageId . '/' . $file . '.bak', $file);
            }
        }
    }
    
    
    /**
     * @param $files
     *
     * @throws \Exception
     */
    public function backupPackageFiles($files)
    {
        foreach ($files as $file) {
            $packageFileSource = $this->fileSystem->getShopDirectory() . '/' . $file;
            
            if (file_exists($packageFileSource) && is_file($packageFileSource)) {
                $this->fileSystem->move($file, 'cache/backup/' . $this->packageId . '/' . $file . '.bak');
            }
        }
    }
}

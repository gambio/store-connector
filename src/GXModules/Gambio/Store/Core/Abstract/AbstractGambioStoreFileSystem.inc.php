<?php
/* --------------------------------------------------------------
   AbstractGambioStoreFileSystem.inc.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/


class AbstractGambioStoreFileSystem
{
    /**
     * Checks the writing permissions of all needed directories for an update by a given update files directory.
     *
     * @param array $fileList
     *
     * @return bool
     */
    public function checkFilesPermissionsWithFileList(array $fileList)
    {
        $wrongPermissions = [];
    
        foreach ($fileList as $shopFile) {
        
            $fileToCheck = $this->getShopFolder() . $shopFile;
            $dirToCheck = dirname($fileToCheck);
            
            if (file_exists($fileToCheck) && ! is_writable($fileToCheck)) {
                $wrongPermissions[] = $fileToCheck;
            }
            
            
            // If directory exists - check if it's writable
            if (file_exists($dirToCheck) && is_dir($dirToCheck)) {
                if (! is_writable($dirToCheck)) {
                    $wrongPermissions[] = $dirToCheck;
                }
            } else {
                // No folder found. Try to create folder...
                if (mkdir($dirToCheck, 0777, true) || is_dir($dirToCheck)) {
                    if (! is_writable($dirToCheck)) {
                        $wrongPermissions[] = $dirToCheck;
                    }
                    
                    @unlink($dirToCheck);
                } else {
                    $wrongPermissions[] = $dirToCheck;
                }
            }
        }
        
        return (bool)$wrongPermissions;
    }
    
    
    protected function fileCopy($source, $destination, $strict = false)
    {
        if (! file_exists($source)) {
            if ($strict) {
                throw new \RuntimeException('No such file: ' . $source);
            }
            
            return false;
        }
        
        $dir = dirname($destination);
        
        if (!file_exists($dir) && !mkdir($dir, 0777, true) && !is_dir($dir) && chmod($dir, 0755)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        
        if (! copy($source, $destination)) {
            throw new \RuntimeException("Couldn't copy file " . $source);
        }
        
        return true;
    }
}

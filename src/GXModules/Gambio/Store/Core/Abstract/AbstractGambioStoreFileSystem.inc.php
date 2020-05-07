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
        
            $fileToCheck = DIR_FS_CATALOG . $shopFile;
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

    
    protected function fileCopy($source, $destination)
    {
        $dir = dirname($destination);
        
        if (!file_exists($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        
        return copy($source, $destination);
    }
}

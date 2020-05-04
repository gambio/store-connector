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
     * @param string $fileList
     *
     * @return array
     */
    public function checkFilesPermissionsWithFileList($fileList)
    {
        $wrongPermittedFiles = [];
        $checkDirectories = [];
        
        foreach ($fileList as $shopFile) {
            $shopTestFile = $shopFile . '.permission_check';
            $shopTestDir = dirname($shopTestFile);
            
            if (in_array($shopTestDir, $checkDirectories)) {
                continue;
            }
            $checkDirectories[] = $shopTestDir;
            
            if (((!file_exists($shopTestDir) || !is_dir($shopTestDir)) && $this->createDirectory($shopTestDir) === false)
                || (file_exists($shopTestDir) && is_dir($shopTestDir) && !is_writeable($shopTestDir))) {
                $wrongPermittedFiles[] = $shopTestDir;
                continue;
            }
            
            $fileOpen = @fopen($shopTestFile, 'w');
            $fileWritten = @fwrite($fileOpen, 'permission test');
            $fileClosed = @fclose($fileOpen);
            
            $this->deleteFile($shopTestFile);
            if ($fileOpen === false || $fileWritten === false || $fileClosed === false
                || (file_exists($shopFile)
                    && !is_writable($shopFile)
                    && !is_writable($shopTestDir))) {
                $wrongPermittedFiles[] = $shopTestDir;
            }
        }
        
        $this->createDebugLog('[UpdateHelper] Check file permissions for a file list', [
            'fileList' => $fileList,
            'wrongPermittedFiles' => $wrongPermittedFiles,
        ]);
        
        return $wrongPermittedFiles;
    }
    
    public function backup()
    {
    
    }
    
    public function restoreBackup()
    {
    
    }
    
    public function cleanCache()
    {
    
    }
}

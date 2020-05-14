<?php
/* --------------------------------------------------------------
   GambioStoreFileSystem.inc.php 2020-05-13
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

require_once 'Exceptions/FileSystemExceptions/FileCopyException.inc.php';
require_once 'Exceptions/FileSystemExceptions/FileNotFoundException.inc.php';
require_once 'Exceptions/FileSystemExceptions/FileRenameException.inc.php';
require_once 'Exceptions/FileSystemExceptions/FileMoveException.inc.php';
require_once 'Exceptions/FileSystemExceptions/CreateDirectoryException.inc.php';
require_once 'Exceptions/FileSystemExceptions/FileRemoveException.inc.php';

class GambioStoreFileSystem
{
    /**
     * Copies file from source to destination.
     * In case folders of destination path are not exist, they will be created.
     *
     * @param      $source
     * @param      $destination
     *
     * @return bool
     * @throws \CreateDirectoryException
     * @throws \FileNotFoundException|\FileCopyException
     */
    private function fileCopy($source, $destination) {
        if (! file_exists($source) || !is_file($source)) {
            throw new FileNotFoundException('No such file: ' . $source);
        }
        
        $this->createDirectory(dirname($destination));
        
        if (! copy($source, $destination)) {
            throw new FileCopyException("Couldn't copy file " . $source);
        }
        
        return true;
    }
    
    
    /**
     * Moves source file from to the destination directory.
     * Creates destination directory recursively in case it doesn't exist.
     *
     * @param $source
     * @param $destination
     *
     * @return bool
     * @throws \FileNotFoundException
     * @throws \CreateDirectoryException
     * @throws \FileMoveException
     */
    public function fileMove($source, $destination)
    {
        if (!file_exists($source) || !is_file($source)) {
            throw new FileNotFoundException('File not found: ' . $source, 1, [
                'info' => "File not found on attempt to move file $source to $destination"
            ]);
        }
    
        if (!file_exists($destination)) {
            $this->createDirectory($destination);
        }
    
        if (! rename($source, $destination)) {
            throw new FileMoveException("Could not move file $source to $destination folder");
        }
    
        return true;
    }
    
    
    /**
     * Crates a directory recursively.
     *
     * @param $path
     *
     * @return bool
     * @throws \CreateDirectoryException
     */
    private function createDirectory($path)
    {
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            
            if (is_file($path)) {
                throw new CreateDirectoryException('Could not create a folder ' . $path, 1, [
                    'info' => 'There is already a file exists for the path: ' . $path
                ]);
            }
            
            if (is_link($path)) {
                throw new CreateDirectoryException('Could not create a folder ' . $path, 2, [
                    'info' => 'There is already a symlink exists for this path! ' . $path
                ]);
            }
    
            throw new CreateDirectoryException('Could not create a folder ' . $path, 3, [
                'info' => 'Please contact the server administrator'
            ]);
        }
        
        return true;
    }
    
    
    /**
     * Renames a file. Any folders for the new name will be ignored.
     *
     * @param $oldFileName
     * @param $newFileName
     *
     * @return bool
     * @throws \FileNotFoundException
     * @throws \FileRenameException
     */
    public function fileRename($oldFileName, $newFileName)
    {
        $newFileBaeName = basename($newFileName);
        
        if (!file_exists($oldFileName) || !is_file($oldFileName)) {
            throw new FileNotFoundException('File not found: ' . $oldFileName, 1, [
                'info' => "File not found on attempt to rename file $oldFileName to $newFileBaeName"
            ]);
        }
    

        if (!rename($oldFileName, dirname($oldFileName) . '/' . $newFileBaeName)) {
            throw new FileRenameException('Could not rename a file ' . $oldFileName, 3, [
                'info' => 'Please contact the server administrator'
            ]);
        }
        
        return true;
    }
    
    
    /**
     * Copies a file or directory from source to destination. If destination folder doesn't exist, it will be created.
     *
     * @param $source
     * @param $destination
     *
     * @throws \CreateDirectoryException
     * @throws \FileCopyException
     * @throws \FileNotFoundException
     */
    public function copy($source, $destination)
    {
        $directory = new RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $this->createDirectory($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                $this->fileCopy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }
    
    
    /**
     * Removes file or folder (including subfolders).
     *
     * @param $dir
     *
     * @return bool
     * @throws \FileRemoveException
     */
    public function removeFileOrFolder($dir)
    {
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->removeFileOrFolder("$dir/$file") : @unlink("$dir/$file");
        }
        
        if (!rmdir($dir)) {
            throw new FileRemoveException("Could not remove file or folder $dir");
        }
        
        return true;
    }
}


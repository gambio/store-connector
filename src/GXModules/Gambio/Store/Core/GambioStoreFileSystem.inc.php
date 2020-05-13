<?php
/* --------------------------------------------------------------
   GambioStoreFileSystem.inc.php 2020-05-13
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

require_once 'Exceptions/FileSystemExceptions/FileCopyException.php';
require_once 'Exceptions/FileSystemExceptions/FileNotFoundException.php';

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
    public function fileCopy($source, $destination) {
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
    public function createDirectory($path)
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
}


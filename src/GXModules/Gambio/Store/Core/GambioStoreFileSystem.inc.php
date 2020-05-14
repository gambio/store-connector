<?php
/* --------------------------------------------------------------
   GambioStoreFileSystem.php 2020-05-14
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'Exceptions/FileSystemExceptions/GambioStoreFileCopyException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreFileMoveException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreFileRenameException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreFileRemoveException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreFileNotFoundException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreDirectoryContentException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStoreCreateDirectoryException.inc.php';
require_once 'Exceptions/FileSystemExceptions/GambioStorePathIsNotDirectoryException.inc.php';

class GambioStoreFileSystem
{
    /**
     * Moves source file from to the destination directory.
     * Creates destination directory recursively in case it doesn't exist.
     *
     * @param $source
     * @param $destination
     *
     * @return bool
     * @throws \GambioStoreFileNotFoundException
     * @throws \GambioStoreFileMoveException
     * @throws \GambioStoreCreateDirectoryException
     */
    public function fileMove($source, $destination)
    {
        if (!file_exists($source) || !is_file($source)) {
            throw new GambioStoreFileNotFoundException('File not found: ' . $source, 1, [
                'info' => "File not found on attempt to move file $source to $destination"
            ]);
        }
        
        if (!file_exists($destination)) {
            $this->createDirectory($destination);
        }
        
        if (!rename($source, $destination)) {
            throw new GambioStoreFileMoveException("Could not move file $source to $destination folder");
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
     * @throws \GambioStoreFileRenameException
     * @throws \GambioStoreFileNotFoundException
     */
    public function fileRename($oldFileName, $newFileName)
    {
        $newFileBaeName = basename($newFileName);
        
        if (!file_exists($oldFileName) || !is_file($oldFileName)) {
            throw new GambioStoreFileNotFoundException('File not found: ' . $oldFileName, 1, [
                'info' => "File not found on attempt to rename file $oldFileName to $newFileBaeName"
            ]);
        }
        
        if (!rename($oldFileName, dirname($oldFileName) . '/' . $newFileBaeName)) {
            throw new GambioStoreFileRenameException('Could not rename a file ' . $oldFileName, 3, [
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
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileCopyException
     * @throws \GambioStoreFileNotFoundException
     */
    public function copy($source, $destination)
    {
        $directory = new RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        
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
     * @param $item
     *
     * @return bool
     * @throws \GambioStoreFileRemoveException
     */
    public function remove($item)
    {
        $files = array_diff(scandir($item), ['.', '..']);
        foreach ($files as $file) {
            is_dir("$item/$file") ? $this->remove("$item/$file") : @unlink("$item/$file");
        }
        
        if (!rmdir($item)) {
            throw new GambioStoreFileRemoveException("Could not remove file or folder $item");
        }
        
        return true;
    }
    
    
    /**
     * Returns all directories from provided directory path.
     *
     * @param $directoryPath
     *
     * @return array|false
     * @throws \GambioStorePathIsNotDirectoryException
     */
    public function getDirectories($directoryPath)
    {
        $this->checkIfPathIsDirectory($directoryPath);
        
        return glob($directoryPath . '/**', GLOB_ONLYDIR);
    }
    
    
    /**
     * Returns all directories from provided directory path.
     *
     * @param string $directoryPath
     *
     * @return array
     * @throws \GambioStoreDirectoryContentException
     * @throws \GambioStorePathIsNotDirectoryException
     * @throws \GambioStoreDirectoryContentException
     */
    public function getDirectoriesRecursively($directoryPath)
    {
        $this->checkIfPathIsDirectory($directoryPath);
        
        try {
            $recursiveDirectories = [];
            $directoryIterator    = new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS);
            
            foreach (new RecursiveIteratorIterator($directoryIterator,
                RecursiveIteratorIterator::SELF_FIRST) as $path) {
                if ($path->isDir()) {
                    $recursiveDirectories[] = $path->__toString();
                }
            }
        } catch (Exception $exception) {
            throw new GambioStoreDirectoryContentException('Could not get content form directory:' . $directoryPath, 0, [],
                $exception);
        }
        
        return $recursiveDirectories;
    }
    
    
    /**
     * Returns all files in provided directory path.
     *
     * @param string $directoryPath
     *
     * @return array|false
     * @throws \GambioStorePathIsNotDirectoryException
     */
    public function getFiles($directoryPath)
    {
        $this->checkIfPathIsDirectory($directoryPath);
        
        return glob($directoryPath . '/*.*');
    }
    
    
    /**
     * Returns all files recursively in the provided directory.
     *
     * @param string $directoryPath
     *
     * @return array
     * @throws \GambioStorePathIsNotDirectoryException|\GambioStoreDirectoryContentException
     */
    public function getFilesRecursively($directoryPath)
    {
        $this->checkIfPathIsDirectory($directoryPath);
        
        try {
            $recursiveFileList = [];
            $directoryIterator = new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS);
            
            foreach (new RecursiveIteratorIterator($directoryIterator,
                RecursiveIteratorIterator::SELF_FIRST) as $path) {
                if ($path->isDir()) {
                    continue;
                }
                $recursiveFileList[] = realpath($path->__toString());
            }
        } catch (Exception $exception) {
            throw new GambioStoreDirectoryContentException('Could not get content form directory:' . $directoryPath, 0, [],
                $exception);
        }
        
        return $recursiveFileList;
    }
    
    
    /**
     * Returns directories and files from directories.
     *
     * @param string $directoryPath
     *
     * @return array|false
     * @throws \GambioStorePathIsNotDirectoryException
     */
    public function getContents($directoryPath)
    {
        $this->checkIfPathIsDirectory($directoryPath);
        
        return glob($directoryPath . '/**');
    }
    
    
    /**
     * Returns the content as array of provided directory path recursively.
     *
     * @param string $directoryPath
     *
     * @return array
     * @throws \GambioStoreDirectoryContentException
     * @throws \GambioStorePathIsNotDirectoryException
     * @throws \GambioStoreDirectoryContentException
     * @throws \GambioStorePathIsNotDirectoryException
     */
    public function getContentsRecursively($directoryPath)
    {
        $this->checkIfPathIsDirectory($directoryPath);
        
        try {
            $recursiveContentsList = [];
            $directoryIterator     = new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS);
            
            foreach (new RecursiveIteratorIterator($directoryIterator,
                RecursiveIteratorIterator::SELF_FIRST) as $path) {
                if ($path->isDir()) {
                    $recursiveContentsList[] = $path->__toString();
                } else {
                    $recursiveContentsList[] = realpath($path->__toString());
                }
            }
        } catch (Exception $exception) {
            throw new GambioStoreDirectoryContentException('Could not get content form directory:' . $directoryPath, 0, [],
                $exception);
        }
        
        return $recursiveContentsList;
    }
    
    
    /**
     * Crates a directory recursively.
     *
     * @param string $path
     *
     * @return bool
     * @throws \GambioStoreCreateDirectoryException
     */
    private function createDirectory($path)
    {
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            
            if (is_file($path)) {
                throw new GambioStoreCreateDirectoryException('Could not create a folder ' . $path, 1, [
                    'info' => 'There is already a file exists for the path: ' . $path
                ]);
            }
            
            if (is_link($path)) {
                throw new GambioStoreCreateDirectoryException('Could not create a folder ' . $path, 2, [
                    'info' => 'There is already a symlink exists for this path! ' . $path
                ]);
            }
            
            throw new GambioStoreCreateDirectoryException('Could not create a folder ' . $path, 3, [
                'info' => 'Please contact the server administrator'
            ]);
        }
        
        return true;
    }
    
    
    /**
     * Copies file from source to destination.
     * In case folders of destination path are not exist, they will be created.
     *
     * @param string $source
     * @param string $destination
     *
     * @return bool
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileNotFoundException|\GambioStoreFileCopyException
     */
    private function fileCopy($source, $destination)
    {
        if (!file_exists($source) || !is_file($source)) {
            throw new GambioStoreFileNotFoundException('No such file: ' . $source);
        }
        
        $this->createDirectory(dirname($destination));
        
        if (!copy($source, $destination)) {
            throw new GambioStoreFileCopyException("Couldn't copy file " . $source);
        }
        
        return true;
    }
    
    
    /**
     * Checks if provided path is a directory.
     *
     * @param string $path
     *
     * @throws \GambioStorePathIsNotDirectoryException
     */
    private function checkIfPathIsDirectory($path)
    {
        if (!is_dir($path)) {
            throw new GambioStorePathIsNotDirectoryException('Path :' . $path . ' is not a directory');
        }
    }
    
    
    /**
     * Returns shop directory path.
     *
     * @return string
     */
    public function getShopDirectory()
    {
        return dirname(__FILE__, 5);
    }
    
    
    /**
     * Returns cache directory path.
     *
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->getShopDirectory() . '/cache';
    }
}


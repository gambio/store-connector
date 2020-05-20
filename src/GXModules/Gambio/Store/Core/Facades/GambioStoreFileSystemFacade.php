<?php
/* --------------------------------------------------------------
   GambioStoreFileSystemFacade.php 2020-05-14
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileCopyException.inc.php';
require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileMoveException.inc.php';
require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileRenameException.inc.php';
require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileNotFoundException.inc.php';
require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreDirectoryContentException.inc.php';
require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreCreateDirectoryException.inc.php';
require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStorePathIsNotDirectoryException.inc.php';

class GambioStoreFileSystemFacade
{
    /**
     * Renames a file. Any folders for the new name will be ignored.
     *
     * @param $oldFileName
     * @param $newFileName
     *
     * @return bool
     * @throws \GambioStoreFileNotFoundException
     * @throws \GambioStoreRenameException
     */
    public function rename($oldFileName, $newFileName)
    {
        $oldFileName    = $this->getShopDirectory() . '/' . $oldFileName;
        
        if (!file_exists($oldFileName)) {
            throw new GambioStoreFileNotFoundException('File not found: ' . $oldFileName, 1, [
                'info' => "File or folder not found on attempt to rename $oldFileName"
            ]);
        }
        
        if (!rename($oldFileName, dirname($oldFileName) . '/' . basename($newFileName))) {
            throw new GambioStoreRenameException('Could not rename a file ir folder ' . $oldFileName, 2, [
                'info' => 'Please contact the server administrator'
            ]);
        }
        
        return true;
    }
    
    
    /**
     * Returns shop directory path.
     *
     * @return string
     */
    public function getShopDirectory()
    {
        return dirname(__FILE__, 6);
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
        $source      = $this->getShopDirectory() . '/' . $source;
        $destination = $this->getShopDirectory() . '/' . $destination;
        
        $directory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $this->createDirectory($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                $this->fileCopy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
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
     * @param $source
     * @param $destination
     *
     * @return bool
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileMoveException
     * @throws \GambioStoreFileNotFoundException
     */
    public function move($source, $destination)
    {
        $source      = $this->getShopDirectory() . '/' . $source;
        $destination = $this->getShopDirectory() . '/' . $destination;
        
        if (!file_exists($source)) {
            throw new GambioStoreFileNotFoundException('');
        }
        
        if (is_file($source)) {
            return $this->fileMove($source, $destination);
        }
        
        $directory = new RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $this->createDirectory($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                $this->fileMove($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
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
     * @throws \GambioStoreFileNotFoundException
     * @throws \GambioStoreFileMoveException
     * @throws \GambioStoreCreateDirectoryException
     */
    private function fileMove($source, $destination)
    {
        if (!file_exists($source) || !is_file($source)) {
            throw new GambioStoreFileNotFoundException('File not found: ' . $source, 1, [
                'info' => "File not found on attempt to move file $source to $destination"
            ]);
        }
        
        if (!file_exists(dirname($destination))) {
            $this->createDirectory(dirname($destination));
        }
        
        if (!rename($source, $destination)) {
            throw new GambioStoreFileMoveException("Could not move file $source to $destination folder");
        }
        
        return true;
    }
    
    
    /**
     * Removes file or folder (including subfolders).
     *
     * @param $path
     *
     * @return bool
     */
    public function remove($path)
    {
        $path = $this->getShopDirectory() . '/' . $path;
        
        if (!is_file($path) && !is_dir($path)) {
            return true;
        }
        
        if (!file_exists($path)) {
            return true;
        }
        
        if (is_file($path)) {
            return @unlink($path);
        }
        
        $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }
        
        @rmdir($path);
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
        
        return glob($directoryPath . '/*', GLOB_ONLYDIR);
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
            throw new GambioStoreDirectoryContentException('Could not get content form directory:' . $directoryPath, 0,
                [], $exception);
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
            throw new GambioStoreDirectoryContentException('Could not get content form directory:' . $directoryPath, 0,
                [], $exception);
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
            throw new GambioStoreDirectoryContentException('Could not get content form directory:' . $directoryPath, 0,
                [], $exception);
        }
        
        return $recursiveContentsList;
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


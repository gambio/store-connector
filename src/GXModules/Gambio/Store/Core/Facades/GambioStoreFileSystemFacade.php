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
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreFileSystemFacade_included')) {
        
        define('GambioStoreFileSystemFacade_included', true);
        
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileCopyException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileMoveException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreRenameException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileNotFoundException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreDirectoryContentException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreCreateDirectoryException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStorePathIsNotDirectoryException.inc.php';
        require_once __DIR__ . '/../Exceptions/FileSystemExceptions/GambioStoreFileExistsException.inc.php';
    
    
        /**
         * This class is used to perform actions on the filesystem within the shop folder.
         * This class is a facade for using the functionality of the GambioStore module by third-party packages
         * during the migration step of the installation/uninstallation process.
         *
         * It mostly reflects the original GambioStoreFileSystem class with some adjustments.
         * However, this class has a magic __call method that is tracking performed actions.
         * This feature allows rolling back filesystem changes made by migrations.
         *
         * Another method presented in the facade class is the __destruct method that removes the migration backup folder.
         *
         * Class GambioStoreFileSystemFacade
         */
        class GambioStoreFileSystemFacade
        {
            /**
             * Migration actions storage.
             *
             * @var array
             */
            private $actionsPerformed = [];
            
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
            public function _rename($oldName, $newName)
            {
                $oldName = $this->getShopDirectory() . '/' . $oldName;
                $newName = dirname($oldName) . '/' . basename($newName);
        
                if (!file_exists($oldName)) {
                    throw new GambioStoreFileNotFoundException('File not found: ' . $oldName, 1, [
                        'info' => "File or folder not found on attempt to rename $oldName"
                    ]);
                }
        
                if (file_exists($newName) && is_file($newName)) {
                    throw new GambioStoreFileExistsException('File already exists: ' . $newName, 1, [
                        'info' => "File with this name already exists on attempt to rename file $oldName to $newName"
                    ]);
                }
        
                if (!rename($oldName, $newName)) {
                    throw new GambioStoreRenameException('Could not rename a file or folder ' . $oldName, 2, [
                        'info' => 'Please contact the server administrator'
                    ]);
                }
            }
            
            
            /**
             * Returns shop directory path.
             *
             * @return string
             */
            public function getShopDirectory()
            {
                return realpath(__DIR__ . '/../../../../..');
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
    
                if (is_file($source)) {
                    $this->fileCopy($source, $destination);
                    return;
                }
    
                $directory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator  = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
    
                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        /**
                         * The getSubPathName method might be highlighted in PhpStorm even though it is exists.
                         * https://www.php.net/manual/en/recursivedirectoryiterator.getsubpathname.php
                         */
                        $this->createDirectory($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                    } else {
                        $sourceFolder = dirname($item->getPathname());
                        $subPath = str_replace($source, '', $sourceFolder);
                        $this->fileCopy($item->getPathname(), $destination . $subPath);
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
            public function createDirectory($path)
            {
                if (is_dir($path)) {
                    return;
                }
        
                if (!@mkdir($path, 0777, true) && !is_dir($path)) {
            
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
            }
            
            
            /**
             * Copies file from source to destination.
             * In case folders of destination path are not exist, they will be created.
             *
             * @param string $source
             * @param string $destination
             *
             * @throws \GambioStoreCreateDirectoryException
             * @throws \GambioStoreFileNotFoundException|\GambioStoreFileCopyException
             */
            private function _fileCopy($source, $destination)
            {
                if (file_exists($destination) && is_file($destination)) {
                    throw new GambioStoreFileExistsException('File already exists: ' . $destination, 1, [
                        'info' => "File with this name already exists on attempt to copy file $source to $destination"
                    ]);
                }
        
                if (!file_exists($source) || !is_file($source)) {
                    throw new GambioStoreFileNotFoundException('No such file: ' . $source);
                }
        
                $this->createDirectory(dirname($destination));
        
                if (!copy($source, $destination)) {
                    throw new GambioStoreFileCopyException("Couldn't copy file " . $source);
                }
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
            private function _fileMove($source, $destination)
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
            public function _remove($path)
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
                    $directoryIterator    = new RecursiveDirectoryIterator($directoryPath,
                        FilesystemIterator::SKIP_DOTS);
                    
                    foreach (new RecursiveIteratorIterator($directoryIterator,
                        RecursiveIteratorIterator::SELF_FIRST) as $path) {
                        if ($path->isDir()) {
                            $recursiveDirectories[] = $path->__toString();
                        }
                    }
                } catch (Exception $exception) {
                    throw new GambioStoreDirectoryContentException('Could not get content form directory:'
                                                                   . $directoryPath, 0, [], $exception);
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
                    throw new GambioStoreDirectoryContentException('Could not get content form directory:'
                                                                   . $directoryPath, 0, [], $exception);
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
                    $directoryIterator     = new RecursiveDirectoryIterator($directoryPath,
                        FilesystemIterator::SKIP_DOTS);
                    
                    foreach (new RecursiveIteratorIterator($directoryIterator,
                        RecursiveIteratorIterator::SELF_FIRST) as $path) {
                        if ($path->isDir()) {
                            $recursiveContentsList[] = $path->__toString();
                        } else {
                            $recursiveContentsList[] = realpath($path->__toString());
                        }
                    }
                } catch (Exception $exception) {
                    throw new GambioStoreDirectoryContentException('Could not get content form directory:'
                                                                   . $directoryPath, 0, [], $exception);
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
    
    
            /**
             * Magic __call method.
             *
             * @param $method
             * @param $arguments
             *
             * @return mixed
             */
            public function __call($method, $arguments)
            {
                $ignoreFileNotFoundException = false;
                switch ($method) {
                    case 'remove':
                        $method = 'fileMove';
                        // Add full path to the shop since we mimic the remove action.
                        $arguments[1]                = $this->getShopDirectory() . '/cache/backup/migrations/' . $arguments[0];
                        $arguments[0]                = $this->getShopDirectory() . '/' . $arguments[0];
                        $ignoreFileNotFoundException = true;
                    case 'fileCopy':
                    case 'fileMove':
                    case 'rename':
                    if ($ignoreFileNotFoundException) {
                        $this->actionsPerformed[] = ['remove', $arguments];
                        try {
                            $returnValue = call_user_func_array([$this, '_' . $method], $arguments);
                        } catch (GambioStoreFileNotFoundException $exception) {
                            // do nothing
                        }
    
                        return $returnValue;
                    }
    
                    $this->actionsPerformed[] = [$method, $arguments];
    
                    return call_user_func_array([$this, '_' . $method], $arguments);
                }
            }
    
    
            /**
             * Destructor removes the migrations backup folder.
             */
            public function __destruct()
            {
                $this->_remove('/cache/backup/migrations/');
            }
    
    
            /**
             * @throws \GambioStoreCreateDirectoryException
             * @throws \GambioStoreFileMoveException
             * @throws \GambioStoreFileNotFoundException
             * @throws \GambioStoreRenameException
             */
            public function rollback()
            {
                $actions = array_reverse($this->actionsPerformed);
                foreach ($actions as $action) {
                    $method = $action[0];
                    switch ($method) {
                        case 'fileCopy':
                            $toRemove = substr($action[1][1], strlen($this->getShopDirectory()) + 1);
                            $this->_remove($toRemove);
                            break;
                        case 'fileMove':
                            $this->_fileMove($action[1][1], $action[1][0]);
                            break;
                        case 'rename':
                            $this->_rename($action[1][1], $action[1][0]);
                            break;
                        case 'remove':
                            try {
                                $this->_fileMove($action[1][1], $action[1][0]);
                            } catch (GambioStoreFileNotFoundException $exception) {
                                // do nothing
                            }
                            break;
                    }
                }
            }
        }
    }
}

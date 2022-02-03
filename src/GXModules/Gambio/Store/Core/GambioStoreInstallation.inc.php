<?php
/* --------------------------------------------------------------
   GambioStoreInstallation.inc.php 2022-02-03
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2022 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
require_once 'Exceptions/GambioStoreException.inc.php';
require_once 'Exceptions/GambioStorePackageInstallationException.inc.php';
require_once 'Exceptions/GambioStoreZipException.inc.php';
require_once 'Exceptions/GambioStoreHttpErrorException.inc.php';
require_once 'Exceptions/GambioStoreFileHashMismatchException.inc.php';
require_once 'GambioStoreHttp.inc.php';
require_once 'GambioStoreCache.inc.php';
require_once 'GambioStoreLogger.inc.php';
require_once 'GambioStoreBackup.inc.php';
require_once 'GambioStoreFileSystem.inc.php';
require_once 'GambioStoreMigration.inc.php';

/**
 * Class StoreInstallation
 *
 * This class performs the package installation. On consecutive calls, it will return the progress of the installation.
 *
 * Before a package gets installed the class checks for the existing package and creates a backup of it by moving
 * all package files to the shop cache directory. If the installation fails at any point, the backup can be restored.
 *
 * The next step is it to get the new package data from the store api by downloading the files into the shop cache
 * directory. To begin with, an attempt is made to download the package zip archive, if this is not possible or
 * goes wrong the fallback of the class is to download each file of the package individually.
 *
 * After the download is successful the class moves the downloaded package files to the desired destination.
 *
 * After moving the files the class runs the migration up scripts to run all the necessary changes for this package
 * version to work. (database changes, file changes, etc).
 *
 * Finally the class removes the backup of the package and changes the progress of the installation to done.
 *
 */
class GambioStoreInstallation
{
    /**
     * @var string Store Token.
     */
    private $token;
    
    /**
     * @var \GambioStoreCache Cache instance.
     */
    private $cache;
    
    /**
     * @var \GambioStoreLogger Logger instance.
     */
    private $logger;
    
    /**
     * @var array Package data.
     */
    private $packageData;
    
    /**
     * @var \GambioStoreBackup
     */
    private $backup;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    /**
     * @var \GambioStoreMigration
     */
    private $migration;
    
    /**
     * @var \GambioStoreHttp
     */
    private $http;
    
    
    /**
     * GambioStoreInstallation constructor.
     *
     * @param array                  $packageData
     * @param string                 $token
     * @param \GambioStoreCache      $cache
     * @param \GambioStoreLogger     $logger
     * @param \GambioStoreFileSystem $filesystem
     * @param \GambioStoreBackup     $backup
     * @param \GambioStoreMigration  $migration
     * @param \GambioStoreHttp       $http
     */
    public function __construct(
        array                 $packageData,
                              $token,
        GambioStoreCache      $cache,
        GambioStoreLogger     $logger,
        GambioStoreFileSystem $filesystem,
        GambioStoreBackup     $backup,
        GambioStoreMigration  $migration,
        GambioStoreHttp       $http
    ) {
        $this->packageData = $packageData;
        $this->token       = $token;
        $this->cache       = $cache;
        $this->logger      = $logger;
        $this->fileSystem  = $filesystem;
        $this->backup      = $backup;
        $this->migration   = $migration;
        $this->http        = $http;
        
        set_error_handler([$this, 'handleUnexpectedError']);
        set_exception_handler([$this, 'handleUnexpectedException']);
    }
    
    
    /**
     * Error handler function.
     *
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     *
     * @throws \Exception
     */
    public function handleUnexpectedError($code, $message, $file, $line)
    {
        if ($code === E_USER_ERROR) {
            $this->cache->delete($this->getTransactionId());
            $this->logger->critical('Critical error during package installation', [
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                    'file'    => $file,
                    'line'    => $line
                ]
            ]);
            $this->backup->restorePackageFilesFromCache($this->getPackageFilesDestinations());
            die();
        }
        
        if ($code !== 2) {
            $this->logger->warning('Minor error during package installation', [
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                    'file'    => $file,
                    'line'    => $line
                ]
            ]);
        }
    }
    
    
    /**
     * Returns unique installation id.
     *
     * @return mixed
     */
    private function getTransactionId()
    {
        return $this->packageData['details']['id'];
    }
    
    
    /**
     * Returns array of installing files.
     *
     * @return array
     */
    private function getPackageFilesDestinations()
    {
        return array_column($this->packageData['fileList']['includedFiles'], 'destination');
    }
    
    
    /**
     * Exception handler function.
     *
     * @param $exception
     *
     * @throws \Exception
     */
    public function handleUnexpectedException(Exception $exception)
    {
        $this->cache->delete($this->getTransactionId());
        $this->logger->critical('Critical exception during package installation', [
            'error'      => [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine()
            ],
            'errorTrace' => $exception->getTrace()
        ]);
        $this->backup->restorePackageFilesFromCache($this->getPackageFilesDestinations());
        die();
    }
    
    
    /**
     * Inits installation.
     *
     * @return bool[]
     * @throws \GambioStoreCacheException
     * @throws \GambioStorePackageInstallationException
     * @throws \Exception
     */
    public function perform()
    {
        if (!$this->cache->has($this->getTransactionId())) {
            $startedProgress = [
                'success'  => true,
                'state'    => 'started',
                'progress' => 0
            ];
            $this->cache->set($this->getTransactionId(), json_encode($startedProgress));
            
            return $startedProgress;
        }
        
        $progress = json_decode($this->cache->get($this->getTransactionId()), true);
        
        try {
            switch ($progress['progress']) {
                case 0:
                    $progressArray = [
                        'success'    => true,
                        'state'      => 'backedup',
                        'progress'   => 20,
                        'clearCache' => true
                    ];
                    $destinations  = $this->getPackageFilesDestinations();
                    $this->backup->movePackageFilesToCache($destinations);
                    
                    $this->cache->set($this->getTransactionId(), json_encode($progressArray));
                    
                    return $progressArray;
                case 20:
                    return $this->nextProgressState(50, 'downloaded', [$this, 'downloadPackageToCacheFolder']);
                case 50:
                    return $this->nextProgressState(80, 'installed', [$this, 'installPackage']);
                case 80:
                    $progressArray = $this->nextProgressState(100, 'migrated', [$this, 'migration', 'up']);
                    
                    $this->placeUpdateNeededFlagIfRequired();
                    
                    $this->cache->delete($this->getTransactionId());
                    $this->cleanCache();
                    
                    return $progressArray;
                default:
                    $message = 'There is no progress sett for transaction id: ' . $this->getTransactionId();
                    $this->cleanCache();
                    $this->cache->delete($this->getTransactionId());
                    throw new GambioStorePackageInstallationException($message);
            }
        } catch (Exception $exception) {
            $this->handleException($exception);
            
            return [];
        }
    }
    
    
    /**
     * Places the update needed flag in the cache directory if the key is present on the cache table.
     *
     * @throws \GambioStoreCacheException
     */
    private function placeUpdateNeededFlagIfRequired()
    {
        $transactionId            = $this->getTransactionId();
        $cacheKey                 = "UPDATE_NEEDED_$transactionId";
        $hasUpdateNeededCacheFlag = $this->cache->has($cacheKey) && $this->cache->get($cacheKey) === true;
        
        if ($hasUpdateNeededCacheFlag) {
            touch($this->fileSystem->getCacheDirectory() . "update_needed.flag");
            $this->cache->delete($cacheKey);
        }
    }
    
    
    /**
     * @param $exception \Exception
     *
     * @throws \GambioStoreCacheException
     * @throws \GambioStorePackageInstallationException
     */
    private function handleException($exception)
    {
        $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
        $this->logger->error($message, [
            'error' => [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine()
            ]
        ]);
        $this->cleanCache();
        $this->cache->delete($this->getTransactionId());
        throw new GambioStorePackageInstallationException($message);
    }
    
    
    /**
     * @param $progress
     * @param $state
     * @param $callback
     *
     * @return array
     * @throws \GambioStoreCacheException
     * @throws \GambioStorePackageInstallationException
     */
    private function nextProgressState($progress, $state, $callback)
    {
        try {
            $destinations = $this->getPackageFilesDestinations();
            
            $progressArray = [
                'success'  => true,
                'state'    => $state,
                'progress' => $progress
            ];
            call_user_func($callback);
            $this->cache->set($this->getTransactionId(), json_encode($progressArray));
            
            return $progressArray;
        } catch (GambioStoreException $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'context' => $e->getContext(),
                'error'   => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine()
                ],
            ]);
            $this->cleanCache();
            $this->restore($destinations);
            $this->cache->delete($this->getTransactionId());
            throw new GambioStorePackageInstallationException($message);
        } catch (Exception $exception) {
            $this->handleException($exception);
        }
    }
    
    
    /**
     * Removes temporary folders created during installation.
     */
    private function cleanCache()
    {
        $this->fileSystem->remove('cache/GambioStore/');
    }
    
    
    /**
     * @param $files
     *
     * @throws \Exception
     */
    private function restore($files)
    {
        $filesToRemove = $this->backup->getDifferenceBetweenBackupAndActualPackage($files);
        foreach ($filesToRemove as $fileToRemove) {
            $this->fileSystem->remove($fileToRemove);
        }
        $this->backup->restorePackageFilesFromCache($files);
        $this->removeEmptyFolders($filesToRemove);
    }
    
    
    /**
     * Remove all empty folders inside themes and GXModules related to this package
     *
     * @param $files
     */
    private function removeEmptyFolders($files)
    {
        // We'll only remove folders inside themes and GXModules
        $foldersOfInterest = array_filter($files, function ($value) {
            return strpos($value, 'themes/') === 0
                   || strpos($value, 'GXModules/') === 0;
        });
        
        // Lets make sure we only have folders
        array_walk($foldersOfInterest, function (&$item) {
            if (!is_dir($this->fileSystem->getShopDirectory() . '/' . $item)) {
                $item = dirname($item);
            }
        });
        
        $foldersOfInterest = array_unique($foldersOfInterest);
        
        // Sort based on length to delete deepest folders first
        usort($foldersOfInterest, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($foldersOfInterest as $foldersToCheck) {
            $this->removeEmptyFoldersRecursively($foldersToCheck);
        }
    }
    
    
    /**
     * Recursively delete each empty folder until either a folder is not empty or we reached themes or GXModules
     *
     * @param $path
     */
    private function removeEmptyFoldersRecursively($path)
    {
        if ($path === 'themes'
            || $path === 'GXModules'
            || !$this->isFolderEmpty($path)) {
            return;
        }
        
        $this->fileSystem->remove($path);
        $this->removeEmptyFoldersRecursively(dirname($path));
    }
    
    
    /**
     * Check if a folder is empty
     *
     * @param $folder
     *
     * @return bool
     */
    private function isFolderEmpty($folder)
    {
        $path = $this->fileSystem->getShopDirectory() . '/' . $folder;
        
        return @count(@scandir($path)) === 2;
    }
    
    
    /**
     * Downloads package into cache folder.
     *
     * @throws \GambioStoreZipException
     * @throws \GambioStoreHttpErrorException
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileHashMismatchException
     */
    private function downloadPackageToCacheFolder()
    {
        if (!extension_loaded('zip')) {
            $this->logger->warning(
                'The Gambio Store could not locate the zip extension for PHP which is required for installations.'
            );
            $this->downloadPackageFilesToCacheFolder();
        } elseif (!$this->downloadPackageZipToCacheFolder()) {
            $this->logger->warning(
                'Could not download zip file: ' . $this->packageData['fileList']['zip']['source'] . ' from package: '
                . $this->packageData['details']['title']['de']
            );
            $this->downloadPackageFilesToCacheFolder();
        }
    }
    
    
    /**
     * Downloads files from the fileList.
     *
     * @return bool
     * @throws \GambioStoreHttpErrorException
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileHashMismatchException
     */
    private function downloadPackageFilesToCacheFolder()
    {
        $packageTempDirectory = $this->fileSystem->getCacheDirectory() . '/' . $this->getTransactionId();
        
        foreach ($this->packageData['fileList']['includedFiles'] as $file) {
            
            $destinationFilePath      = $packageTempDirectory . '/' . $file['destination'];
            $destinationFileDirectory = dirname($destinationFilePath);
            $this->fileSystem->createDirectory($destinationFileDirectory);
            
            $fileContent = $this->getFileContent($file['source']);
            file_put_contents($destinationFilePath, $fileContent);
            
            if (md5_file($destinationFilePath) !== $file['hash']) {
                throw new GambioStoreFileHashMismatchException('Uploaded file has wrong hash.', [
                    'file' => $destinationFilePath
                ]);
            }
        }
        
        return true;
    }
    
    
    /**
     * Downloads zip archive to cache folder.
     *
     * @return bool
     * @throws \GambioStoreZipException|\GambioStoreHttpErrorException
     * @throws \GambioStoreFileHashMismatchException|\GambioStoreCreateDirectoryException
     */
    private function downloadPackageZipToCacheFolder()
    {
        $destinationFileName = $this->getTransactionId() . '.zip';
        $destinationFilePath = $this->fileSystem->getCacheDirectory() . '/' . $destinationFileName;
        $this->fileSystem->createDirectory(dirname($destinationFilePath));
        $downloadZipUrl = $this->packageData['fileList']['zip']['source'];
        $fileContent    = $this->getFileContent($downloadZipUrl);
        file_put_contents($destinationFilePath, $fileContent);
        
        chmod($destinationFilePath, 0777);
        
        if (md5_file($destinationFilePath) !== $this->packageData['fileList']['zip']['hash']) {
            throw new GambioStoreFileHashMismatchException('Uploaded package zip file has wrong hash.', [
                'file' => $destinationFilePath
            ]);
        }
        
        $zip = new ZipArchive;
        $res = $zip->open($destinationFilePath);
        if ($res !== true) {
            throw new GambioStoreZipException('Cannot extract zip archive.', [
                'file' => $destinationFilePath
            ]);
        }
        
        $zip->extractTo($this->fileSystem->getCacheDirectory() . '/' . $this->getTransactionId());
        $zip->close();
        
        return true;
    }
    
    
    /**
     * Performs Curl requests to download file from the provided url.
     *
     * @param       $url
     *
     * @return string
     * @throws \GambioStoreHttpErrorException
     */
    private function getFileContent($url)
    {
        $response = $this->http->get($url, [
            CURLOPT_HTTPHEADER => ["X-ACCESS-TOKEN: $this->token"]
        ]);
        
        $code = $response->getInformation('http_code');
        
        if ($code !== 200) {
            throw new GambioStoreHttpErrorException('Error on download a file', [
                'info'  => "Couldn't download a file via $url.",
                'token' => $this->token
            ]);
        }
        
        return $response->getBody();
    }
    
    
    /**
     * Installs a package.
     *
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileMoveException
     * @throws \GambioStoreFileNotFoundException
     * @throws \GambioStoreCacheException
     */
    private function installPackage()
    {
        $fileList     = $this->getPackageFilesDestinations();
        $updateNeeded = false;
        
        foreach ($fileList as $file) {
            if ($this->isCacheFile($file)) {
                $updateNeeded = true;
                continue;
            }
            
            $this->moveFile($file);
        }
        
        if ($updateNeeded) {
            $this->setUpdateNeededFlag();
        }
        
        $this->logger->notice('Successfully installed package : ' . $this->packageData['details']['title']['de']);
    }
    
    
    /**
     * Determines whether the given fileName is the update_needed.flag.
     *
     * @param $file
     *
     * @return bool
     */
    private function isCacheFile($file)
    {
        return basename($file) === "update_needed.flag";
    }
    
    
    /**
     * Move the file to its new destination.
     *
     * @param $file
     *
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileMoveException
     * @throws \GambioStoreFileNotFoundException
     */
    private function moveFile($file)
    {
        $newPackageFile = 'cache/GambioStore/' . $this->getTransactionId() . '/' . $file;
        
        // Replace the old package file with new
        $this->fileSystem->move($newPackageFile, $file);
    }
    
    
    /**
     * Set the update needed flag in the cache for the current transaction.
     *
     * @throws \GambioStoreCacheException
     */
    private function setUpdateNeededFlag()
    {
        $transactionId = $this->getTransactionId();
        $this->cache->set("UPDATE_NEEDED_$transactionId", true);
    }
}

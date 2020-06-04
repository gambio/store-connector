<?php
/* --------------------------------------------------------------
   GambioStoreInstallation.inc.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
require_once 'Exceptions/GambioStoreException.inc.php';
require_once 'Exceptions/GambioStoreCurlFileDownloadException.inc.php';
require_once 'Exceptions/GambioStorePackageInstallationException.inc.php';
require_once 'Exceptions/GambioStoreInstallationMissingPHPExtensionsException.inc.php';
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
 * Performs a Store package installation and take care of all the required actions.
 *
 * Execute the upgrade script if needed.
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
        array $packageData,
        $token,
        GambioStoreCache $cache,
        GambioStoreLogger $logger,
        GambioStoreFileSystem $filesystem,
        GambioStoreBackup $backup,
        GambioStoreMigration $migration,
        GambioStoreHttp $http
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
     * Exception handler function.
     *
     * @param $exception
     *
     * @throws \Exception
     */
    public function handleUnexpectedException($exception)
    {
        $this->logger->critical('Critical error during package installation', ['error' => $exception]);
        $this->backup->restorePackageFilesFromCache($this->getPackageFilesDestinations());
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
     * Inits installation.
     *
     * @return bool[]
     * @throws \GambioStoreCacheException
     * @throws \GambioStorePackageInstallationException
     * @throws \Exception
     */
    public function perform()
    {
        if (!extension_loaded('zip')) {
            $message = 'The Gambio Store could not locate the zip extension for PHP which is required for installations.';
            $this->logger->critical($message);
            throw new GambioStoreInstallationMissingPHPExtensionsException($message);
        }
        
        if (!extension_loaded('curl')) {
            $message = 'The Gambio Store could not locate the curl extension for PHP which is required for installations.';
            $this->logger->critical($message);
            throw new GambioStoreInstallationMissingPHPExtensionsException($message);
        }
        
        if ($this->cache->has($this->getTransactionId())) {
            return $this->cache->get($this->getTransactionId());
        }
        
        $destinations = $this->getPackageFilesDestinations();
        
        try {
            $this->backup->movePackageFilesToCache($destinations);
        } catch (Exception $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e,
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->cleanCache();
            throw new GambioStorePackageInstallationException($message);
        }
        
        try {
            $this->downloadPackageToCacheFolder();
            $this->installPackage();
            $this->migration->up();
        } catch (GambioStoreException $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'context'          => $e->getContext(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->restore($destinations);
            throw new GambioStorePackageInstallationException($message);
        } catch (Exception $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e,
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->restore($destinations);
            throw new GambioStorePackageInstallationException($message);
        }
        finally {
            $this->cleanCache();
        }
    
        $this->logger->notice('Successfully installed package : ' . $this->packageData['details']['title']['de']);
    
        return ['success' => true];
    }
    
    
    /**
     * @param $files
     *
     * @throws \Exception
     */
    private function restore($files)
    {
        $this->backup->restorePackageFilesFromCache($files);
        $filesToRemove = $this->backup->getDifferenceBetweenBackupAndActualPackage($files);
        foreach ($filesToRemove as $fileToRemove) {
            $this->fileSystem->remove($fileToRemove);
        }
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
        if (!$this->downloadPackageZipToCacheFolder()) {
            $this->logger->warning('Could not download zip file: ' . $this->packageData['fileList']['zip']['source']
                                   . ' from package: ' . $this->packageData['details']['title']['de']);
            $this->downloadPackageFilesToCacheFolder();
        }
    }
    
    
    /**
     * Installs a package.
     *
     * @throws \GambioStoreCreateDirectoryException
     * @throws \GambioStoreFileMoveException
     * @throws \GambioStoreFileNotFoundException
     */
    private function installPackage()
    {
        foreach ($this->getPackageFilesDestinations() as $file) {
            $newPackageFile = 'cache/GambioStore/' . $this->getTransactionId() . '/' . $file;
            
            // Replace the old package file with new
            $this->fileSystem->move($newPackageFile, $file);
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
     * Performs Curl requests.
     *
     * @param       $url
     *
     * @return string
     * @throws \GambioStoreHttpErrorException
     */
    public function getFileContent($url)
    {
        $response = $this->http->get($url, [
            CURLOPT_HTTPHEADER => ["X-STORE-TOKEN: $this->token"]
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
     * Removes temporary folders created during installation.
     */
    private function cleanCache()
    {
        $this->fileSystem->remove('cache/GambioStore/');
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
}

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
    private $filesystem;
    
    /**
     * @var \GambioStoreMigration
     */
    private $migration;
    
    
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
     */
    public function __construct(
        array $packageData,
        $token,
        GambioStoreCache $cache,
        GambioStoreLogger $logger,
        GambioStoreFileSystem $filesystem,
        GambioStoreBackup $backup,
        GambioStoreMigration $migration
    ) {
        $this->packageData = $packageData;
        $this->token       = $token;
        $this->cache       = $cache;
        $this->logger      = $logger;
        $this->filesystem  = $filesystem;
        $this->backup      = $backup;
        $this->migration   = $migration;
        
        register_shutdown_function([$this, 'registerShutdownFunction']);
    }
    
    
    /**
     * Shutdown callback function.
     *
     * @throws \Exception
     */
    public function registerShutdownFunction()
    {
        if ($error = error_get_last()) {
            $this->logger->critical('Critical error during package installation', ['error' => $error]);
            $this->backup->restorePackageFilesFromCache($this->getPackageFilesDestinations());
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
            $this->downloadPackageToCacheFolder();
            $this->backup->movePackageFilesToCache($destinations);
            $this->installPackage();
            $this->migration->up();
        } catch (GambioStoreException $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'context'          => $e->getContext(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->backup->restorePackageFilesFromCache($destinations);
            throw new GambioStorePackageInstallationException($message);
        } catch (Exception $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->backup->restorePackageFilesFromCache($destinations);
            throw new GambioStorePackageInstallationException($message);
        }
        finally {
            $this->cleanCache();
        }
        
        $this->logger->notice('Successfully installed package : ' . $this->packageData['details']['title']['de']);
        
        return ['success' => true];
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
            $this->filesystem->move($newPackageFile, $file);
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
        $packageTempDirectory = $this->filesystem->getCacheDirectory() . '/' . $this->getTransactionId();
        
        foreach ($this->packageData['fileList']['includedFiles'] as $file) {
            
            $destinationFilePath      = $packageTempDirectory . '/' . $file['destination'];
            $destinationFileDirectory = dirname($destinationFilePath);
            $this->filesystem->createDirectory($destinationFileDirectory);
            
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
        $destinationFilePath = $this->filesystem->getCacheDirectory() . '/' . $destinationFileName;
        $this->filesystem->createDirectory(dirname($destinationFilePath));
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
        
        $zip->extractTo($this->filesystem->getCacheDirectory() . '/' . $this->getTransactionId());
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
        $http     = new GambioStoreHttp;
        $response = $http->get($url, [
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
        $this->filesystem->remove('cache/GambioStore/');
    }
}

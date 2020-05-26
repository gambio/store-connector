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
require_once 'Exceptions/GambioStoreCurlFileDownloadException.inc.php';
require_once 'Exceptions/GambioStorePackageInstallationException.inc.php';
require_once 'Exceptions/GambioStoreInstallationMissingPHPExtensionsException.inc.php';
require_once 'Exceptions/GambioStoreZipException.inc.php';
require_once 'Exceptions/GambioStoreHttpErrorException.inc.php';

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
     * GambioStoreInstallation constructor.
     *
     * @param array                  $packageData
     * @param string                 $token
     * @param \GambioStoreCache      $cache
     * @param \GambioStoreLogger     $logger
     * @param \GambioStoreFileSystem $filesystem
     * @param \GambioStoreBackup     $backup
     */
    public function __construct(
        array $packageData,
        $token,
        GambioStoreCache $cache,
        GambioStoreLogger $logger,
        GambioStoreFileSystem $filesystem,
        GambioStoreBackup $backup
    ) {
        $this->packageData = $packageData;
        $this->token       = $token;
        $this->cache       = $cache;
        $this->logger      = $logger;
        $this->filesystem  = $filesystem;
        $this->backup      = $backup;
        
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
            $this->logger->critical('Critical error during package installation', $error);
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
     * @return mixed
     */
    private function getPackageMigrations()
    {
        return $this->packageData['migrations'] ? : [];
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
        
        $destination = $this->getPackageFilesDestinations();
        
        try {
            $this->downloadPackageToCacheFolder();
            $this->backup->movePackageFilesToCache($destination);
            $this->installPackage();
            $this->migrate();
        } catch (GambioStoreCreateDirectoryException $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->backup->restorePackageFilesFromCache($destination);
            throw new GambioStorePackageInstallationException('Could not install package');
        } catch (GambioStoreFileNotFoundException $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->backup->restorePackageFilesFromCache($destination);
            throw new GambioStorePackageInstallationException('Could not install package');
        } catch (GambioStoreFileMoveException $e) {
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
            $this->backup->restorePackageFilesFromCache($destination);
            throw new GambioStorePackageInstallationException('Could not install package');
        } catch (Exception $e) {
            $this->backup->restorePackageFilesFromCache($destination);
            $message = 'Could not install package: ' . $this->packageData['details']['title']['de'];
            $this->logger->error($message, [
                'error'            => $e->getMessage(),
                'packageVersionId' => $this->packageData['details']['id']
            ]);
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
            $newPackageFile = 'cache/' . $this->getTransactionId() . '/' . $file;
            
            // Replace the old package file with new
            $this->filesystem->move($newPackageFile, $file);
        }
    }
    
    
    /**
     * Runs package migrations.
     *
     * @throws \GambioStoreUpMigrationException
     */
    private function migrate()
    {
        if (!isset($this->getPackageMigrations()['up'])) {
            return;
        }
        
        $migration = new GambioStoreMigration(
            $this->filesystem,
            $this->getPackageMigrations()['up'],
            []
        );
        
        $migration->up();
    }
    
    /**
     * Downloads files from the fileList.
     *
     * @return bool
     * @throws \GambioStoreHttpErrorException
     * @throws \GambioStoreCreateDirectoryException
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
        }
        
        return true;
    }
    
    
    /**
     * Downloads zip archive to cache folder.
     *
     * @return bool
     * @throws \GambioStoreZipException|\GambioStoreHttpErrorException
     */
    private function downloadPackageZipToCacheFolder()
    {
        $targetFileName = $this->getTransactionId() . '.zip';
        $targetFilePath = $this->filesystem->getCacheDirectory() . '/' . $targetFileName;
        $downloadZipUrl = $this->packageData['fileList']['zip']['source'];
        $fileContent    = $this->getFileContent($downloadZipUrl);
        file_put_contents($targetFilePath, $fileContent);
        
        chmod($targetFilePath, 0777);
        
        /** @todo check the logic here. For some reason the hashes don't match */ //if (md5_file($targetFilePath) !== $this->packageData['fileList']['zip']['hash']) {
        //    $this->logger->error('Uploaded package zip file has wrong hash.');
        //    return false;
        //}
        
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res !== true) {
            throw new GambioStoreZipException('Cannot extract zip archive.', [
                'file' => $targetFilePath
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
        $targetFilePath = 'cache/' . $this->getTransactionId() . '.zip';
        if (file_exists($this->filesystem->getShopDirectory() . '/' . $targetFilePath)) {
            $this->filesystem->remove($targetFilePath);
        }
        
        $targetFilePath = 'cache/' . $this->getTransactionId();
        if (file_exists($this->filesystem->getShopDirectory() . '/' . $targetFilePath)) {
            $this->filesystem->remove($targetFilePath);
        }
        
        $this->backup->removePackageFilesFromCache($this->getPackageFilesDestinations());
    }
}

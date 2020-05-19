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
        
        if ($this->cache->has($this->getTransactionId())) {
            return $this->cache->get($this->getTransactionId());
        }
        
        try {
            $this->logger->info('Try to install package', ['packageData' => $this->packageData]);
            $this->logger->info('Start by downloading package into cache directory');
            $this->downloadPackageToCacheFolder();
            $this->logger->info('Creating backup in cache directory');
            $this->backup->movePackageFilesToCache($this->getPackageFilesDestinations());
            $this->logger->info('Installing package');
            $this->installPackage();
        } catch (GambioStoreCreateDirectoryException $e) {
            $this->logger->warning($e->getMessage());
        } catch (GambioStoreFileNotFoundException $e) {
            $this->logger->warning($e->getMessage());
        } catch (GambioStoreFileMoveException $e) {
            $this->logger->warning($e->getMessage());
        } catch (GambioStoreCurlFileDownloadException $e) {
            $this->logger->warning($e->getMessage());
        } catch (Exception $e) {
            $this->backup->restorePackageFilesFromCache($this->getPackageFilesDestinations());
            $message = 'Could not install package';
            $this->logger->error($message, ['error' => $e]);
            throw new GambioStorePackageInstallationException($message);
        }
        finally {
            $this->cleanCache();
            $this->logger->info('Removing backup and download from cache directory');
        }
        
        $this->logger->info('succeed');
        return ['success' => true];
    }
    
    
    /**
     * Downloads package into cache folder.
     *
     * @throws \GambioStoreCurlFileDownloadException
     */
    private function downloadPackageToCacheFolder()
    {
        if (!$this->downloadPackageZipToCacheFolder()) {
            $this->logger->notice('Could not download zip file');
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
     * Downloads files from the fileList.
     *
     * @return bool
     * @throws \GambioStoreCurlFileDownloadException
     */
    private function downloadPackageFilesToCacheFolder()
    {
        $this->logger->info('Try to download each file separately');
        $packageTempDirectory = $this->filesystem->getCacheDirectory() . '/' . $this->getTransactionId();
        
        foreach ($this->packageData['fileList']['includedFiles'] as $file) {
            
            $destinationFilePath      = $packageTempDirectory . '/' . $file['destination'];
            $destinationFileDirectory = dirname($destinationFilePath);
            if (!file_exists($destinationFileDirectory) && !mkdir($destinationFileDirectory, 0777, true)
                && !is_dir($destinationFileDirectory)) {
                $this->logger->error('Cannot create a folder in the cache directory. Please check permissions.');
                
                return false;
            }
            
            $destinationFile = fopen($destinationFilePath, 'wb+');
            $this->curlFileDownload($file['source'], [CURLOPT_FILE => $destinationFile]);
            $this->curlFileDownload($file['source'], [CURLOPT_FILE => $destinationFile]);
            fclose($destinationFile);
        }
        
        $this->logger->info('Files downloaded successfully');
        return true;
    }
    
    
    /**
     * Downloads zip archive to cache folder.
     *
     * @return bool
     * @throws \GambioStoreCurlFileDownloadException
     * @throws \GambioStoreZipException
     */
    private function downloadPackageZipToCacheFolder()
    {
        $this->logger->info('Try to download zip file');
        $targetFileName = $this->getTransactionId() . '.zip';
        $targetFilePath = $this->filesystem->getCacheDirectory() . '/' . $targetFileName;
        $zipFile        = fopen($targetFilePath, 'wb+');
        $downloadZipUrl = $this->packageData['fileList']['zip']['source'];
        $this->curlFileDownload($downloadZipUrl, [CURLOPT_FILE => $zipFile]);
        fclose($zipFile);
        
        chmod($targetFilePath, 0777);
        
        /** @todo check the logic here. For some reason the hashes don't match */ //if (md5_file($targetFilePath) !== $this->packageData['fileList']['zip']['hash']) {
        //    $this->logger->error('Uploaded package zip file has wrong hash.');
        //    return false;
        //}
        
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res !== true) {
            throw new GambioStoreZipException('Cannot extract zip archive.', [
                'file' => $zipFile
            ]);
        }
        
        $zip->extractTo($this->filesystem->getCacheDirectory() . '/' . $this->getTransactionId());
        $zip->close();
        
        $this->logger->info('Zip file downloaded successfully');
        return true;
    }
    
    
    /**
     * Performs Curl requests.
     *
     * @param       $url
     * @param array $options
     *
     * @throws \GambioStoreCurlFileDownloadException
     */
    public function curlFileDownload($url, $options = [])
    {
        $curlOptions = $options + [
                CURLOPT_URL        => $url,
                CURLOPT_HTTPHEADER => ["X-STORE-TOKEN: $this->token"]
            ];
        
        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $curl_success = curl_exec($ch);
        $curl_errno   = curl_errno($ch);
        $curl_error   = curl_error($ch);
        
        curl_close($ch);
        
        if ($curl_success === false) {
            $message = sprintf('%s - %s', $curl_errno, $curl_error);
            $this->logger->error($message);
            throw new GambioStoreCurlFileDownloadException($message);
        }
    }
    
    
    /**
     * Removes temporary folders created during installation.
     */
    private function cleanCache()
    {
        $targetFilePath = 'cache/' . $this->getTransactionId() . '.zip';
        file_exists($this->filesystem->getShopDirectory() . '/' . $targetFilePath)
        && $this->filesystem->remove($targetFilePath);
        
        $targetFilePath = 'cache/' . $this->getTransactionId();
        file_exists($this->filesystem->getShopDirectory() . '/' . $targetFilePath)
        && $this->filesystem->remove($targetFilePath);
    }
}

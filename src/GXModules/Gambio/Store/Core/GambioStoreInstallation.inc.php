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
require_once 'Abstract/AbstractGambioStoreFileSystem.inc.php';
require_once 'Exceptions/FileDownloadException.inc.php';
require_once 'Exceptions/WrongFilePermissionException.inc.php';
require_once 'Exceptions/CreateFolderException.inc.php';

/**
 * Class StoreInstallation
 *
 * Performs a Store package installation and take care of all the required actions.
 *
 * Execute the upgrade script if needed.
 */
class GambioStoreInstallation extends AbstractGambioStoreFileSystem
{
    private $token;
    
    private $cache;
    
    private $fileList;
    /**
     * @var string
     */
    private $cacheFolder;
    
    private $logger;
    /**
     * @var array
     */
    private $packageFilesDestinations;
    /**
     * @var array
     */
    private $includeFiles;
    /**
     * @var mixed
     */
    private $transactionId;
    /**
     * @var string
     */
    private $shopFolder;
    private $packageData;
    
    
    public function __construct($packageData, $token, $cache, $logger)
    {
        $this->packageData  = $packageData;
        $this->token       = $token;
        $this->cache       = $cache;
        $this->logger      = $logger;
        $this->shopFolder = dirname(__FILE__, 5);
        $this->cacheFolder = dirname(__FILE__, 5) . '/cache/';
        $this->includeFiles = array_column($packageData['fileList']['includedFiles'], 'destination');
        $this->transactionId = $packageData['details']['id'];
    }
    
    
    public function perform()
    {
        if ($this->cache->has($this->transactionId)) {
            return $this->cache->get($this->transactionId);
        }

        try {
            $this->downloadPackageToCacheFolder();
            $this->installPackage();
            //$this->cleanCache();
        } catch (Exception $e) {
            throw new PackageInstallationException($e->getMessage());
        }
    }
    
    private function downloadPackageToCacheFolder()
    {
        $downloaded = $this->downloadPackageFromZipToCacheFolder() ?: $this->downLoadPackageFilesToCacheFolder();
        
        if (! $downloaded) {
            throw new DownloadPackageException('Could not download package');
        }
    }
    
    private function installPackage()
    {
        foreach ($this->includeFiles as $file) {
        
            $shopFile = $this->shopFolder . '/' . $file;
            $backupFile = $this->cacheFolder . 'backup/' . $file . '.bak';
            $newPackageFile = $this->cacheFolder . $this->transactionId .  '/' . $file;
        
            $toRestore = [];
            try {
                // Backup
                $this->fileCopy($shopFile, $backupFile);
                // Replace the old package file witn new
            
                if ($this->fileCopy($newPackageFile, $shopFile)) {
                    $toRestore[] = $file;
                }
            } catch (Exception $e) {
                $this->restorePackageFromBackup($toRestore);
            }
        }
        
        // @todo clean cache (remove zip, remove backup)
    }

    
    private function downLoadPackageFilesToCacheFolder()
    {
        $packageTempDirectory = $this->cacheFolder . $this->transactionId;
        
        if (!mkdir($packageTempDirectory) && !is_dir($packageTempDirectory)) {
            $this->logger->error('Cannot create a folder in the cache directory. Please check permissions.');
            return false;
        }
        
        foreach ($this->includeFiles as $file) {
    
            $destinationFilePath = $this->cacheFolder . $file['destination'];
            
            try {
                $this->curlFileDownload($file['source'], [CURLOPT_FILE => $destinationFilePath]);
            } catch (CurlFileDownloadException $e) {
                $this->logger->error($e->getMessage());
                return false;
            }
            
            if (hash_file('md5', $destinationFilePath) !== $file['hash']) {
                $this->logger->error('File hash check fails for file ' . $destinationFilePath);
                return false;
            }
        }
        
        return true;
    }
    
    private function downloadPackageFromZipToCacheFolder()
    {
        $targetFileName = $this->transactionId . '.zip';
        $targetFilePath = $this->cacheFolder . $targetFileName;
        $zipFile = fopen($targetFilePath, 'wb+');
        $dounloadZipUrl = $this->packageData['fileList']['zip']['source'];
    
        // @todo remove the test
        // $dounloadZipUrl = 'http://localhost/projects/store-api/public/index.php/files/netdexx/netdexx001/v1.0.1';
    
        //try {
        //    $this->curlFileDownload($dounloadZipUrl, [CURLOPT_FILE => $zipFile]);
        //} catch (CurlFileDownloadException $e) {
        //    fclose($zipFile);
        //    $this->logger->error($e->getMessage());
        //    return false;
        //}
    
        fclose($zipFile);
    
        chmod($targetFilePath, 0777);
    
        /** @todo check the logic here. For some reason the hashes don't match */
        //if (md5_file($targetFilePath) !== $this->packageData['fileList']['zip']['hash']) {
        //    $this->logger->error('Uploaded package zip file has wrong hash.');
        //    return false;
        //}
    
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res !== true) {
            $this->logger->error('Cannot extract zip archive for id ' . $this->fileList['id']);
            $zip->close();
            return false;
        }
    
        $zip->extractTo($this->cacheFolder . $this->transactionId);
        $zip->close();
    
        return true;
    }
    
    public function curlFileDownload($url, $options = [])
    {
        $curlOptions = array_merge($options, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => ["X-STORE-TOKEN: $this->token"]
        ]);
    
        $ch             = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $curl_success = curl_exec($ch);
        $curl_errno   = curl_errno($ch);
        $curl_error   = curl_error($ch);
    
        curl_close($ch);

        if ($curl_success === false) {
            throw new CurlFileDownloadException(sprintf('%s - %s', $curl_errno, $curl_error));
        }
    }
    
    private function restorePackageFromBackup($toRestore)
    {
        foreach ($toRestore as $file) {
            $shopFile = $this->shopFolder . '/' . $file;
            $backupFile = $this->cacheFolder . 'backup/' . $file . '.bak';
            
            try {
                $this->fileCopy($backupFile, $shopFile);
            } catch (Exception $e) {
                throw $e;
            }
        }
    }
}

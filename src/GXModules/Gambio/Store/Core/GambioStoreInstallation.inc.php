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
require_once 'Exceptions/FileDownloadException.inc.php';
require_once 'Exceptions/WrongFilePermissionException.inc.php';
require_once 'Exceptions/CreateFolderException.inc.php';
require_once 'Exceptions/PackageInstallationException.inc.php';

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
     * @var \GambioStoreGambioStoreBackup
     */
    private $backup;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $filesystem;
    
    
    /**
     * GambioStoreInstallation constructor.
     *
     * @param $packageData
     * @param $token
     * @param $cache
     * @param $logger
     * @param $filesystem
     */
    public function __construct($packageData, $token, $cache, $logger, $filesystem)
    {
        $this->packageData = $packageData;
        $this->token       = $token;
        $this->cache       = $cache;
        $this->logger      = $logger;
        $this->backup      = new GambioStoreGambioStoreBackup($this->getTransactionId(), $filesystem);
        $this->filesystem  = $filesystem;
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
     * @throws \PackageInstallationException|\GambioStoreCacheException
     */
    public function perform()
    {
        if ($this->cache->has($this->getTransactionId())) {
            return $this->cache->get($this->getTransactionId());
        }
        
        try {
            $this->downloadPackageToCacheFolder();
            $this->backup->backupPackageFiles($this->getPackageFilesDestinations());
            $this->installPackage();
        } catch (Exception $e) {
            
            // Log everything here
            throw new PackageInstallationException($e->getMessage());
        }
        finally {
            $this->cleanCache();
        }
        
        return ['success' => true];
    }
    
    
    /**
     * Downloads package into cache folder.
     *
     * @throws \DownloadPackageException
     */
    private function downloadPackageToCacheFolder()
    {
        $downloaded = $this->downloadPackageFromZipToCacheFolder() ? : $this->downloadPackageFilesToCacheFolder();
        
        if (!$downloaded) {
            throw new DownloadPackageException('Could not download package');
        }
    }
    
    
    /**
     * Installing package.
     *
     * @throws \Exception
     */
    private function installPackage()
    {
        try {
            foreach ($this->getPackageFilesDestinations() as $file) {
                $shopFile       = $file;
                $newPackageFile = 'cache/' . $this->getTransactionId() . '/' . $file;
                
                // Replace the old package file with new
                $this->filesystem->move($newPackageFile, $shopFile);
            }
        } catch (Exception $e) {
            $this->backup->restoreBackUp($this->getPackageFilesDestinations());
        }
    }
    
    
    /**
     * Downloads files from the filelist.
     *
     * @return bool
     */
    private function downloadPackageFilesToCacheFolder()
    {
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
            
            try {
                $this->curlFileDownload($file['source'], [CURLOPT_FILE => $destinationFile]);
            } catch (CurlFileDownloadException $e) {
                $this->logger->error($e->getMessage());
                
                return false;
            }
            finally {
                fclose($destinationFile);
            }
        }
        
        return true;
    }
    
    
    /**
     * Downloads zip archive to cache folder.
     *
     * @return bool
     */
    private function downloadPackageFromZipToCacheFolder()
    {
        $targetFileName = $this->getTransactionId() . '.zip';
        $targetFilePath = $this->filesystem->getCacheDirectory() . '/' . $targetFileName;
        $zipFile        = fopen($targetFilePath, 'wb+');
        $downloadZipUrl = $this->packageData['fileList']['zip']['source'];
        
        try {
            $this->curlFileDownload($downloadZipUrl, [CURLOPT_FILE => $zipFile]);
        } catch (CurlFileDownloadException $e) {
            $this->logger->error($e->getMessage());
            
            return false;
        }
        finally {
            fclose($zipFile);
        }
        
        chmod($targetFilePath, 0777);
        
        /** @todo check the logic here. For some reason the hashes don't match */ //if (md5_file($targetFilePath) !== $this->packageData['fileList']['zip']['hash']) {
        //    $this->logger->error('Uploaded package zip file has wrong hash.');
        //    return false;
        //}
        
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res !== true) {
            $this->logger->error('Cannot extract zip archive for id ' . $this->getTransactionId());
            $zip->close();
            
            return false;
        }
        
        $zip->extractTo($this->filesystem->getCacheDirectory() . '/' . $this->getTransactionId());
        $zip->close();
        
        return true;
    }
    
    
    /**
     * Performs Curl requests.
     *
     * @param       $url
     * @param array $options
     *
     * @throws \CurlFileDownloadException
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
            throw new CurlFileDownloadException(sprintf('%s - %s', $curl_errno, $curl_error));
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

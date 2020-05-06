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
    
    
    public function __construct($fileList, $token, $cache, $logger)
    {
        $this->fileList = $fileList;
        $this->token = $token;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->cacheFolder = DIR_FS_CATALOG . '/cache/';
    }
    
    
    public function perform()
    {
        if ($this->cache->has($this->fileList['id'])) {
            return $this->cache->get($this->fileList['id']);
        }
    
        $this->cache->set($this->fileList['id'], json_encode(['state' => 'start', 'progress' => 0]));
    
        $this->install();
    }
    
    private function install()
    {
        try {
            $this->downloadToCacheFolder();
            $this->copyFilesFromCacheFolder();
        } catch (WrongFilePermissionException $e) {
            throw $e;
        } catch (DownloadPackageException $e) {
            $this->cleanCache();
            throw $e;
        }
    }
    
    private function copyFilesFromCacheFolder()
    {
        foreach ($this->fileList as $file) {
            
            $shopFile = DIR_FS_CATALOG . '/' . $file['destination'];
            $backupFile = $this->cacheFolder . '/backup/' . $file['destination'] . '.bak';
            $newPackageFile = $this->cacheFolder . '/' . $this->fileList['id'] . $file['destination'];
    
            try {
                // Backup installed file to cache folder.
                if (file_exists($shopFile)) {
                    $this->fileCopy($shopFile, $backupFile);
                }
    
                // Copy new file to shop
                if (file_exists($newPackageFile)) {
                    $this->fileCopy($newPackageFile, $shopFile);
                }
            } catch (Exception $e) {
                $this->restoreBackup();
                throw $e;
            }
        }
    }
    
    private function fileCopy($source, $destination)
    {
        $dir = dirname($destination);
    
        if (!file_exists($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    
        copy($source, $destination);
    }
    
    private function downloadToCacheFolder()
    {
        if (! is_writable($this->cacheFolder) && ! chmod($this->cacheFolder, 0777)) {
            throw new WrongFilePermissionException("Folder $this->cacheFolder is not writable");
        }

        $downloaded = $this->downloadPackageFromZipToCacheFolder() ?: $this->downLoadPackageFilesToCacheFolder();
        
        if (! $downloaded) {
            throw new DownloadPackageException('Could not download package');
        }
    }
    
    private function downLoadPackageFilesToCacheFolder()
    {
        $files = $this->fileList['includedFiles'];
        $packageTempDirectory = $this->cacheFolder . $this->fileList['id'];
        
        if (!mkdir($packageTempDirectory) && !is_dir($packageTempDirectory)) {
            $this->logger->error('Cannot create a folder in the cache directory. Please check permissions.');
            return false;
        }
        
        foreach ($files as $file) {
    
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
        $targetFileName = $this->fileList['id'] . '.zip';
        $targetFilePath = $this->cacheFolder . $targetFileName;
        $zipFile = fopen($targetFilePath, 'wb+');
    
        try {
            $this->curlFileDownload($targetFilePath, [CURLOPT_FILE => $zipFile]);
        } catch (CurlFileDownloadException $e) {
            fclose($zipFile);
            $this->logger->error($e->getMessage());
            return false;
        }
        
        fclose($zipFile);
        
        if (hash_file('md5', $targetFilePath) !== $this->fileList['zip']['hash']) {
            $this->logger->error('Uploaded package zip file has wrong hash.');
            return false;
        }
        
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res === true) {
            $zip->extractTo($this->fileList['id']);
        } else {
            $this->logger->error('Cannot extract zip archive for id ' . $this->fileList['id']);
            $zip->close();
            return false;
        }
    
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
    
    public function restoreBackup()
    {
        foreach ($this->fileList as $file) {
            $shopFile = DIR_FS_CATALOG . '/' . $file['destination'];
            $backupFile = $this->cacheFolder . '/backup/' . $file['destination'] . '.bak';
    
            if (file_exists($shopFile)) {
                $this->fileCopy($backupFile, $shopFile);
            }
        }
    }
}

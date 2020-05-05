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
    
    
    public function perform($data, $name)
    {
        if ($this->cache->has()) {
            return $this->cache->get();
        }
    
        $this->cache->set($this->fileList['id'], json_encode(['state' => 'start', 'progress' => 0]));
    
        $this->install();
    }
    
    private function install()
    {
        try {
            $this->downloadToCache();
        } catch(WrongFilePermissionException $e) {
            throw $e;
        } catch(DownloadPackageException $e) {
            $this->cleanCache();
            throw $e;
        }
        
        $this->copyFilesFromCacheFolder();
    }
    
    private function copyFilesFromCacheFolder()
    {
    
    }
    
    private function downloadToCache()
    {
        if (! is_writable($this->cacheFolder) && ! chmod($this->cacheFolder, 0777)) {
            throw new WrongFilePermissionException("Folder $this->cacheFolder is not writable");
        }

        $downloaded = $this->downloadPackageFromZipToCacheFolder() ?: $this->downLoadPackageFilesToCacheFolder();
        
        if (! $downloaded) {
            throw new DownloadPackageException('Could not download package');
        }
    }
    
    
    /**
     * @return bool
     */
    private function downLoadPackageFilesToCacheFolder()
    {
        $files = $this->fileList['includedFiles'];
        $packageTempDirectory = $this->cacheFolder . $this->fileList['id'];
        
        if (!mkdir($packageTempDirectory) && !is_dir($packageTempDirectory)) {
            $this->logger->error('Cannot create a folder in the cache directory. Please check permissions.');
            return false;
        }
        
        foreach ($files as $file) {
            try {
                $this->curlFileDownload($file['source'], [CURLOPT_FILE => $this->cacheFolder . $file['destination']]);
            } catch (CurlFileDownloadException $e) {
                $this->logger->error($e->getMessage());
                return false;
            }
            
            if (hash_file('md5', $this->cacheFolder . $file['destination']) !== $file['hash']) {
                $this->logger->error('Cannot create a folder in the cache directory. Please check permissions.');
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
}

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

/**
 * Class StoreInstallation
 *
 * Performs a Store package installation and take care of all the required actions.
 *
 * Execute the upgrade script if needed.
 */
class GambioStoreInstallation extends AbstractGambioStoreFileSystem
{
    const CACHE_FOLDER = '';
    
    private $token;
    
    private $cache;
    
    private $fileList;
    /**
     * @var string
     */
    private $cacheFolder;
    
    
    public function __construct($fileList, $token, $cache)
    {
        $this->fileList = $fileList;
        $this->token = $token;
        $this->cache = $cache;
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
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
    
    private function downloadToCache()
    {
        if (! is_writable($this->cacheFolder) && ! chmod($this->cacheFolder, 0777)) {
            throw new RuntimeException("Folder $this->cacheFolder is not writable");
        }
        
        try {
            $this->downloadPackageFromZipToCacheFolder();
        } catch (Exception $e) {
            $this->downLoadPackageFilesToCacheFolder();
        } finally {
            $this->cleanCache();
        }
    }
    
    
    private function downLoadPackageFilesToCacheFolder()
    {
        $files = $this->fileList['includedFiles'];
        $packageTempDirectory = $this->cacheFolder . $this->fileList['id'];
        
        if (!mkdir($packageTempDirectory) && !is_dir($packageTempDirectory)) {
            throw new RuntimeException('Cannot create a folder in the cache directory. Please check permissions.');
        }
        
        foreach ($files as $file) {
            $this->curlFileDownload($file['source'], [CURLOPT_FILE => $this->cacheFolder . $file['destination']]);
            if (hash_file('md5', $this->cacheFolder . $file['destination']) !== $file['hash']) {
                throw new \RuntimeException('Uploaded package zip file has wrong hash.');
            }
        }
    }
    
    
    private function downloadPackageFromZipToCacheFolder()
    {
        $targetFileName = $this->fileList['id'] . '.zip';
        $targetFilePath = $this->cacheFolder . $targetFileName;
        $zipFile = fopen($targetFilePath, 'wb+');
    
        try {
            $this->curlFileDownload($targetFilePath, [CURLOPT_FILE => $zipFile]);
        } catch (Exception $e) {
            fclose($zipFile);
            throw new \RuntimeException($e->getMessage());
        }
        
        fclose($zipFile);
        
        if (hash_file('md5', $targetFilePath) !== $this->fileList['zip']['hash']) {
            throw new \RuntimeException('Uploaded package zip file has wrong hash.');
        }
        
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res === true) {
            $zip->extractTo($this->fileList['id']);
            $zip->close();
        } else {
            $zip->close();
            throw new \RuntimeException('Cannot extract zip archive');
        }
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
            throw new \RuntimeException(sprintf('%s - %s', $curl_errno, $curl_error));
        }
    }
}

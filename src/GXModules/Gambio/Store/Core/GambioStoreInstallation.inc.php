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
    
    
    public function __construct($fileList, $token, $cache)
    {
        $this->fileList = $fileList;
        $this->token = $token;
        $this->cache = $cache;
    }
    
    
    public function perform($data, $name)
    {
        $this->downloadToCache();
    }
    
    private function downloadToCache()
    {
        try {
            $this->downloadPackageFromZipToCacheFolder();
        } catch (Exception $e) {
            $this->downLoadPackageFilesToCacheFolder();
        }
    }
    
    
    private function downLoadPackageFilesToCacheFolder()
    {
    }
    
    
    private function downloadPackageFromZipToCacheFolder()
    {
        $targetFileName = $this->fileList['ic'] . '.zip';
        $targetFilePath = DIR_FS_CATALOG . '/cache/' . $targetFileName;
        $zipFile = fopen($targetFilePath, 'wb+');
    
        try {
            $this->curlFileDownload($targetFilePath, [CURLOPT_FILE => $zipFile]);
        } catch (Exception $e) {
            fclose($zipFile);
            throw new \RuntimeException($e->getMessage());
        }
        
        fclose($zipFile);
        
        $zip = new ZipArchive;
        $res = $zip->open($targetFilePath);
        if ($res === true) {
            $zip->extractTo($this->fileList['ic']);
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

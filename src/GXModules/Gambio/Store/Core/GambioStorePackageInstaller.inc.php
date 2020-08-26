<?php
/* --------------------------------------------------------------
   GambioStorePackageInstaller.php 2020-07-08
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStorePackageInstaller
 *
 * This class is responsible for installing/uninstalling packages.
 */
class GambioStorePackageInstaller
{
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    /**
     * @var \GambioStoreConfiguration
     */
    private $configuration;
    
    /**
     * @var \GambioStoreCache
     */
    private $cache;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    /**
     * @var \GambioStoreBackup
     */
    private $backup;
    
    /**
     * @var \GambioStoreThemes
     */
    private $themes;
    
    
    /**
     * GambioStorePackageInstaller constructor.
     *
     * @param \GambioStoreFileSystem    $fileSystem
     * @param \GambioStoreConfiguration $configuration
     * @param \GambioStoreCache         $cache
     * @param \GambioStoreLogger        $logger
     * @param \GambioStoreBackup        $backup
     * @param \GambioStoreThemes        $themes
     */
    public function __construct(
        GambioStoreFileSystem $fileSystem,
        GambioStoreConfiguration $configuration,
        GambioStoreCache $cache,
        GambioStoreLogger $logger,
        GambioStoreBackup $backup,
        GambioStoreThemes $themes
    ) {
        $this->fileSystem    = $fileSystem;
        $this->configuration = $configuration;
        $this->cache         = $cache;
        $this->logger        = $logger;
        $this->backup        = $backup;
        $this->themes        = $themes;
    }
    
    
    /**
     * Sets shop offline.
     */
    private function setShopOffline()
    {
        $this->configuration->set('GM_SHOP_OFFLINE', 'checked');
    }
    
    
    /**
     * Sets shop online.
     */
    private function setShopOnline()
    {
        $this->configuration->set('GM_SHOP_OFFLINE', '');
    }
    
    
    /**
     * @return bool indicating wether the shop is online.
     */
    private function isShopOnline()
    {
        return $this->configuration->get('GM_SHOP_OFFLINE') !== 'checked';
    }
    
    
    /**
     * Installs a package.
     *
     * @param $packageData
     *
     * @return bool[]
     * @throws \Exception
     */
    public function installPackage($packageData)
    {
        $wasShopOnline = $this->isShopOnline();
        
        $migration = new GambioStoreMigration($this->fileSystem,
            isset($packageData['migrations']['up']) ? $packageData['migrations']['up'] : [],
            isset($packageData['migrations']['down']) ? $packageData['migrations']['down'] : []);
        
        $http = new GambioStoreHttp();
        
        $installation = new GambioStoreInstallation($packageData, $this->configuration->get('GAMBIO_STORE_TOKEN'),
            $this->cache, $this->logger, $this->fileSystem, $this->backup, $migration, $http);
        
        try {
            if ($wasShopOnline) {
                $this->setShopOffline();
            }
            $response = $installation->perform();
        } catch (Exception $exception) {
            restore_error_handler();
            restore_exception_handler();
            
            if ($wasShopOnline) {
                $this->setShopOnline();
            }
            
            throw $exception;
        }
        
        if ($response['progress'] === 100) {
            
            if (isset($packageData['details']['folder_name_inside_shop'])
                || isset($packageData['details']['filename'])) {
                $themeDirectoryName = $packageData['details']['folder_name_inside_shop'] ? : $packageData['details']['filename'];
                $this->themes->reimportContentManagerEntries($themeDirectoryName);
            }
            
            restore_error_handler();
            restore_exception_handler();
        }
        
        return $response;
    }
    
    
    /**
     * Uninstalls a package.
     *
     * @param array $postData
     *
     * @return bool[]
     * @throws \Exception
     */
    public function uninstallPackage(array $postData)
    {
        $packageData         = [];
        $packageData['name'] = $postData['title']['de'];
        
        if (isset($postData['folder_name_inside_shop']) || isset($postData['filename'])) {
            $themeDirectoryName = $postData['folder_name_inside_shop'] ? : $postData['filename'];
            $themeDirectoryPath = $this->fileSystem->getThemeDirectory() . '/' . $themeDirectoryName;
            
            try {
                $fileList = $this->fileSystem->getContentsRecursively($themeDirectoryPath);
            } catch (GambioStoreException $exception) {
                $message = 'Could not install package: ' . $postData['details']['title']['de'];
                $this->logger->error($message, [
                    'context' => $exception->getContext(),
                    'error'   => [
                        'code'    => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'file'    => $exception->getFile(),
                        'line'    => $exception->getLine()
                    ],
                ]);
                
                throw $exception;
            }
            $shopDirectoryPathLength = strlen($this->fileSystem->getShopDirectory() . '/');
            array_walk($fileList, function (&$item) use ($shopDirectoryPathLength) {
                $item = substr($item, $shopDirectoryPathLength);
            });
            $packageData['files_list'] = $fileList;
        } else {
            $packageData['files_list'] = $postData['file_list'];
        }
        
        $migration = new GambioStoreMigration($this->fileSystem,
            isset($postData['migrations']['up']) ? $postData['migrations']['up'] : [],
            isset($postData['migrations']['down']) ? $postData['migrations']['down'] : []);
        
        $removal = new GambioStoreRemoval($packageData, $this->logger, $this->backup, $migration, $this->fileSystem);
        
        $wasShopOnline = $this->isShopOnline();
        
        try {
            if ($wasShopOnline) {
                $this->setShopOffline();
            }
            $response = $removal->perform();
        } catch (Exception $exception) {
            restore_error_handler();
            restore_exception_handler();
            throw $exception;
        }
        finally {
            if ($wasShopOnline) {
                $this->setShopOnline();
            }
        }
        
        restore_error_handler();
        restore_exception_handler();
        
        return $response;
    }
}


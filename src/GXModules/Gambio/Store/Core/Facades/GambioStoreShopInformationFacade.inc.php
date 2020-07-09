<?php
/* --------------------------------------------------------------
   GambioStoreShopInformationFacade.php 2020-07-09
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreShopInformationFacade_included')) {
        
        define('GambioStoreShopInformationFacade_included', true);
        require_once __DIR__ . '/../Exceptions/GambioStoreHttpServerMissingException.inc.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreRelativeShopPathMissingException.inc.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreShopKeyMissingException.inc.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreShopVersionMissingException.inc.php';
        
        
        class GambioStoreShopInformationFacade
        {
            /**
             * @var \GambioStoreDatabaseFacade
             */
            private $database;
            
            /**
             * @var \GambioStoreFileSystemFacade
             */
            private $fileSystem;
            
            
            /**
             * GambioStoreShopInformation constructor.
             *
             * @param \GambioStoreDatabaseFacade   $database
             * @param \GambioStoreFileSystemFacade $fileSystem
             */
            public function __construct(GambioStoreDatabaseFacade $database, GambioStoreFileSystemFacade $fileSystem)
            {
                $this->database   = $database;
                $this->fileSystem = $fileSystem;
                require_once $this->fileSystem->getShopDirectory() . '/admin/includes/configure.php';
            }
            
            
            /**
             * Returns the shop information of the Shop
             *
             * @return array
             * @throws \GambioStoreHttpServerMissingException
             * @throws \GambioStoreRelativeShopPathMissingException
             * @throws \GambioStoreShopKeyMissingException
             * @throws \GambioStoreShopVersionMissingException
             */
            public function getShopInformation()
            {
                return [
                    'version' => 3,
                    'shop'    => [
                        'url'     => $this->getShopUrl(),
                        'key'     => $this->getShopKey(),
                        'version' => $this->getShopVersion()
                    ],
                    'server'  => [
                        'phpVersion'   => $this->getPhpVersion(),
                        'mySQLVersion' => $this->getMySQLVersion()
                    ],
                    'modules' => $this->getModuleVersionFiles(),
                    'themes'  => $this->getThemes()
                ];
            }
            
            
            /**
             * Returns the shop URL
             *
             * @return string
             * @throws \GambioStoreHttpServerMissingException
             * @throws \GambioStoreRelativeShopPathMissingException
             */
            private function getShopUrl()
            {
                if (!defined('HTTP_SERVER')) {
                    throw new GambioStoreHttpServerMissingException('The HTTP Server constant is missing from the configure.php file in admin.');
                }
                
                if (!defined('DIR_WS_CATALOG')) {
                    throw new GambioStoreRelativeShopPathMissingException('The DIR_WS_CATALOG constant is missing from the configure.php file in admin.');
                }
                
                return HTTP_SERVER . DIR_WS_CATALOG;
            }
            
            
            /**
             * Returns the shop key
             *
             * @return mixed
             * @throws \GambioStoreShopKeyMissingException
             */
            private function getShopKey()
            {
                if (!defined('GAMBIO_SHOP_KEY')) {
                    throw new GambioStoreShopKeyMissingException('The GAMBIO_SHOP_KEY constant is missing from the shop.');
                }
                
                return GAMBIO_SHOP_KEY;
            }
            
            
            /**
             * Returns the Shop version
             *
             * @return mixed
             */
            private function getShopVersion()
            {
                require $this->fileSystem->getShopDirectory() . '/release_info.php';
                
                if (!isset($gx_version)) {
                    throw new GambioStoreShopVersionMissingException('The release_info.php no longer includes a $gx_version variable or the file is missing.');
                }
                
                return $gx_version;
            }
            
            
            /**
             * Returns the PHP Version
             *
             * @return string
             */
            private function getPhpVersion()
            {
                return PHP_VERSION;
            }
            
            
            /**
             * Returns the MySQL Version
             *
             * @return string
             */
            private function getMySQLVersion()
            {
                return $this->database->getVersion();
            }
            
            
            /**
             * Returns the files within the version_info folder of the shop
             *
             * @return array|false
             */
            private function getModuleVersionFiles()
            {
                $versionFiles = [];
                
                foreach (new DirectoryIterator($this->fileSystem->getShopDirectory() . '/version_info') as $file) {
                    if ($file->isFile() && strpos($file->getFilename(), '.php')) {
                        $versionFiles[] = $file->getFilename();
                    }
                }
                
                return $versionFiles;
            }
            
            
            /**
             * Returns all the folders in the themes directory and tries to see if they have a version
             *
             * @return array|false
             */
            private function getThemes()
            {
                $themes = [];
                
                foreach (new DirectoryIterator($this->fileSystem->getShopDirectory() . '/themes') as $directory) {
                    if ($directory->isDir() && !$directory->isDot()) {
                        $themeJsonContents = @file_get_contents($directory->getPathname() . '/theme.json');
                        if ($themeJsonContents) {
                            $themeJson = json_decode($themeJsonContents, true);
                            if ($themeJson !== null) {
                                $themes[$directory->getFilename()] = $themeJson['version'];
                            }
                        }
                    }
                }
                
                return $themes;
            }
        }
    }
}

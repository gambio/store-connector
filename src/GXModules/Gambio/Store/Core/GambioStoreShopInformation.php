<?php
/* --------------------------------------------------------------
   GambioStoreShopInformation.php 2020-05-13
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once "Exceptions/GambioStoreHttpServerMissingException.php";
require_once "Exceptions/GambioStoreRelativeShopPathMissingException.php";
require_once "Exceptions/GambioStoreShopKeyMissingException.php";
require_once "Exceptions/GambioStoreShopVersionMissingException.php";


class GambioStoreShopInformation
{
    /**
     * @var GambioStoreDatabase
     */
    private $database;
    
    
    /**
     * GambioStoreShopInformation constructor.
     *
     * @param \GambioStoreDatabase $database
     */
    public function __construct(GambioStoreDatabase $database)
    {
        $this->database = $database;
        require_once __DIR__ . '/../../../../admin/includes/configure.php';
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
        require __DIR__ . '/../../../../release_info.php';
        
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
        return phpversion();
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
        
        foreach (new DirectoryIterator(__DIR__ . '/../../../../version_info') as $file) {
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
        
        foreach (new DirectoryIterator(__DIR__ . '/../../../../themes') as $directory) {
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
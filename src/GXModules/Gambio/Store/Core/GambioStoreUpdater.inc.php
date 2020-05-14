<?php
/* --------------------------------------------------------------
   GambioStoreUpdater.php 2020-05-08
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../GambioStoreConnector.inc.php';


/**
 * Class GambioStoreUpdater
 *
 * Updates the Gambio Store Connector
 */
class GambioStoreUpdater
{
    public function update()
    {
        $this->updateMenu();
        $this->createDatabaseKeysIfNotExists();
        $this->createCacheTableIfNotExists();
    }
    
    
    public function updateMenu()
    {
        $menuPath = __DIR__ . '/../../../../system/conf/admin_menu/gambio_menu.xml';
        
        $menuContent = file_get_contents($menuPath);
        
        $gambioMenuRegex = '/.<menugroup id="BOX_HEADING_GAMBIO_STORE.*\s.*\s.*\s.*\s.<\/menugroup>/i';
        
        $menuContent = preg_replace($gambioMenuRegex, '', $menuContent);
        
        file_put_contents($menuPath, $menuContent);
    }
    
    
    public function createDatabaseKeysIfNotExists()
    {
        $connector = GambioStoreConnector::getInstance();
        
        $configuration = $connector->getConfiguration();
        
        if (!$configuration->has('GAMBIO_STORE_URL')) {
            $configuration->create('GAMBIO_STORE_URL', 'https://store.gambio.com/a');
        }
        
        if (!$configuration->has('GAMBIO_STORE_TOKEN')) {
            $gambioToken = $connector->generateToken();
            $configuration->create('GAMBIO_STORE_TOKEN', $gambioToken);
        }
        
        if (!$configuration->has('GAMBIO_STORE_IS_REGISTERED')) {
            $configuration->create('GAMBIO_STORE_IS_REGISTERED', 'false');
        }
        
        if (!$configuration->has('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING')) {
            if ($configuration->has('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING')) {
                $value = $configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING');
                $configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', $value);
            } else {
                $configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', 'false');
            }
        }
    }
    
    
    public function createCacheTableIfNotExists()
    {
        //        CREATE TABLE IF NOT EXISTS gambio_store_cache (
        //        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        //	      cache_key VARCHAR(30) NOT NULL,
        //	      cache_value TEXT NOT NULL
        //        ) ENGINE=INNODB
    }
}

$updater = new GambioStoreUpdater();
$updater->update();

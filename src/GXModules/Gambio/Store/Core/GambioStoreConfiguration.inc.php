<?php
/* --------------------------------------------------------------
   GambioStoreConfiguration.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class StoreConfiguration
 *
 * This class enables read/write operations to the shop's configuration table, using the key/value paradigm.
 *
 * These operations differ depending on the shop version but this class makes sure data are being read correctly.
 */
class GambioStoreConfiguration
{
    /**
     * @var \GambioStoreDatabase
     */
    private $database;
    
    /**
     * @var \GambioStoreCompatibility
     */
    private $compatibility;
    
    
    /**
     * StoreConfiguration constructor.
     *
     * @param \GambioStoreDatabase      $database
     * @param \GambioStoreCompatibility $compatibility
     */
    public function __construct(\GambioStoreDatabase $database, \GambioStoreCompatibility $compatibility)
    {
        $this->database      = $database;
        $this->compatibility = $compatibility;
    }
    
    
    /**
     * Returns the configuration value of the provided key.
     *
     * @param string $key
     */
    public function get($key)
    {
        if ($this->compatibility->has(GambioStoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE)) {
            return $this->gmGet($key);
        }
        
        return $this->gxGet($key);
    }
    
    
    /**
     * Returns the value from gm configurations
     *
     * @param $key
     *
     * @return mixed|null
     */
    private function gmGet($key)
    {
        $statement = $this->database->query('SELECT gm_value FROM gm_configuration WHERE gm_key = :key',
            ['key' => $key]);
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return null;
        }
        return $result['gm_value'];
    }
    
    
    /**
     * Returns the value from gx configurations
     *
     * @param $key
     *
     * @return mixed|null
     */
    private function gxGet($key)
    {
        $statement = $this->database->query('SELECT `value` FROM gx_configurations WHERE `key` = :key',
            ['key' => $key]);
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return null;
        }
        
        return $result['value'];
    }
    
    
    /**
     * Sets the configuration value with the provided key.
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        if ($this->compatibility->has(GambioStoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE)) {
            $this->gmSet($key, $value);
        } else {
            $this->gxSet($key, $value);
        }
    }
    
    
    /**
     * Sets the value from gm configurations
     *
     * @param $key
     * @param $value
     */
    private function gmSet($key, $value)
    {
        $this->database->query('UPDATE gm_configuration SET gm_value = :value WHERE gm_key = :key',
            [':value' => $value, ':key' => $key]);
    }
    
    
    /**
     * Sets the value from gx configurations
     *
     * @param $key
     * @param $value
     */
    private function gxSet($key, $value)
    {
        $this->database->query('UPDATE gx_configurations SET `value` = :value WHERE `key` = :key',
            [':value' => $value, ':key' => $key]);
    }
}

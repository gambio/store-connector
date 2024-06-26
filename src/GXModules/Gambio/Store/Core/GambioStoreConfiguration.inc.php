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
 * Class GambioStoreConfiguration
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
     * GambioStoreConfiguration constructor.
     *
     * @param \GambioStoreDatabase      $database
     * @param \GambioStoreCompatibility $compatibility
     */
    public function __construct(GambioStoreDatabase $database, GambioStoreCompatibility $compatibility)
    {
        $this->database      = $database;
        $this->compatibility = $compatibility;
    }
    
    
    /**
     * Returns the configuration value of the provided key.
     *
     * @param string $key
     *
     * @return bool|mixed|null
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
        
        if ($result['gm_value'] === 'false' || $result['gm_value'] === 'true') {
            $result['gm_value'] = $result['gm_value'] !== 'false';
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
            ['key' => 'gm_configuration/' . $key]);
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return null;
        }
        
        if ($result['value'] === 'false' || $result['value'] === 'true') {
            $result['value'] = $result['value'] !== 'false';
        }
        
        return $result['value'];
    }
    
    
    /**
     * Sets the configuration value with the provided key.
     *
     * @param string       $key
     * @param string| bool $value
     */
    public function set($key, $value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        
        if ($this->compatibility->has(GambioStoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE)) {
            $this->gmSet($key, $value);
            
            return;
        }
        
        $this->gxSet($key, $value);
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
            [':value' => $value, ':key' => 'gm_configuration/' . $key]);
    }
    
    
    /**
     * Removes the configuration value with the provided key.
     *
     * @param string $key
     */
    public function remove($key)
    {
        if (!empty($key)) {
            if ($this->compatibility->has(GambioStoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE)) {
                $this->gmRemove($key);
                
                return;
            }
            
            $this->gxRemove($key);
        }
    }
    
    
    /**
     * Remove the value from gm configurations
     *
     * @param $key
     */
    private function gmRemove($key)
    {
        $this->database->query('DELETE FROM gm_configuration WHERE gm_key = :key', [':key' => $key]);
    }
    
    
    /**
     * Remove the value from gx configurations
     *
     * @param $key
     */
    private function gxRemove($key)
    {
        $this->database->query('DELETE FROM gx_configurations `key` = :key', [':key' => 'gm_configuration/' . $key]);
    }
    
    
    /**
     * Checks if it has the configuration key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if ($this->compatibility->has(GambioStoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE)) {
            return $this->gmHas($key);
        }
        
        return $this->gxHas($key);
    }
    
    
    /**
     * Checks if gm configuration has the key
     *
     * @param $key
     *
     * @return bool
     */
    private function gmHas($key)
    {
        $statement = $this->database->query('SELECT gm_value FROM gm_configuration WHERE gm_key = :key',
            [':key' => $key]);
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        return !($result === false);
    }
    
    
    /**
     * Checks if gx configurations has the key
     *
     * @param $key
     *
     * @return bool
     */
    private function gxHas($key)
    {
        $statement = $this->database->query('SELECT `value` FROM gx_configurations WHERE `key` = :key',
            [':key' => 'gm_configuration/' . $key]);
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        return !($result === false);
    }
    
    
    /**
     * Creates the configuration value with the provided key.
     *
     * @param  $key
     * @param  $value
     */
    public function create($key, $value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        
        if ($this->compatibility->has(GambioStoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE)) {
            $this->gmCreate($key, $value);
        } else {
            $this->gxCreate($key, $value);
        }
    }
    
    
    /**
     * Creates the value from gm configurations
     *
     * @param $key
     * @param $value
     */
    private function gmCreate($key, $value)
    {
        $this->database->query('INSERT INTO gm_configuration (gm_key, gm_value) VALUES (:key, :value)',
            [':value' => $value, ':key' => $key]);
    }
    
    
    /**
     * Creates the value from gx configurations
     *
     * @param $key
     * @param $value
     */
    private function gxCreate($key, $value)
    {
        $this->database->query('INSERT INTO gx_configurations (`key`, `value`) VALUES (:key, :value)',
            [':value' => $value, ':key' => 'gm_configuration/' . $key]);
    }
}

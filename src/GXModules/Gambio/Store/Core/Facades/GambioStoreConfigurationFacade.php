<?php
/* --------------------------------------------------------------
   GambioStoreConfigurationFacade.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStoreConfigurationFacade
 *
 * This class enables read/write operations to the shop's configuration table, using the key/value paradigm.
 *
 * These operations differ depending on the shop version but this class makes sure data are being read correctly.
 */
class GambioStoreConfigurationFacade
{
    /**
     * @var \GambioStoreDatabaseFacade
     */
    private $database;
    
    /**
     * @var \GambioStoreCompatibilityFacade
     */
    private $compatibility;
    
    
    /**
     * GambioStoreConfigurationFacade constructor.
     *
     * @param \GambioStoreDatabaseFacade      $database
     * @param \GambioStoreCompatibilityFacade $compatibility
     */
    public function __construct(GambioStoreDatabaseFacade $database, GambioStoreCompatibilityFacade $compatibility)
    {
        $this->database      = $database;
        $this->compatibility = $compatibility;
    }
    
    
    /**
     * Returns the configuration value of the provided key.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        if ($this->compatibility->has(GambioStoreCompatibilityFacade::RESOURCE_GM_CONFIGURATION_TABLE)) {
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
        $statement = $this->database->query('SELECT `gm_value` FROM `gm_configuration` WHERE `gm_key` = :key',
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
        $statement = $this->database->query('SELECT `value` FROM `gx_configurations` WHERE `key` = :key',
            ['key' => 'gm_configuration/' . $key]);
        
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
        if ($this->compatibility->has(GambioStoreCompatibilityFacade::RESOURCE_GM_CONFIGURATION_TABLE)) {
            $this->gmSet($key, $value);
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
     * Checks if it has the configuration key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if ($this->compatibility->has(GambioStoreCompatibilityFacade::RESOURCE_GM_CONFIGURATION_TABLE)) {
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
        $statement = $this->database->query('SELECT `value` FROM gx_configuration WHERE `key` = :key',
            [':key' => 'gm_configuration/' . $key]);
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        return !($result === false);
    }
    
    
    /**
     * Creates the configuration value with the provided key.
     *
     * @param string $key
     * @param string $value
     */
    public function create($key, $value)
    {
        if ($this->compatibility->has(GambioStoreCompatibilityFacade::RESOURCE_GM_CONFIGURATION_TABLE)) {
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

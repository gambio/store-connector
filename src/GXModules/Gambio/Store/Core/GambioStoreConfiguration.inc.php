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
        
    }
    
    
    /**
     * Sets the configuration value with the provided key.
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        
    }
}

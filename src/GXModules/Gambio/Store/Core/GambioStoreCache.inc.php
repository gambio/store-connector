<?php
/* --------------------------------------------------------------
   GambioStoreCache.inc.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'GambioStoreDatabase.inc.php';

/**
 * Class GambioStoreCache
 *
 * Cache implementation for Gambio Store.
 *
 * Execute the downgrade script if needed.
 */
class GambioStoreCache
{
    /**
     * Cache table constant.
     */
    const CACHE_TABLE = 'gambio_store_cache';
    
    /**
     * @var
     */
    private $database;
    
    
    /**
     * GambioStoreCache constructor.
     *
     * @param $database
     */
    public function __construct($database)
    {
        $this->database = $database;
    }
    
    
    /**
     * Returns data from cache.
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $sql = 'SELECT * FROM ' . self::CACHE_TABLE .  ' WHERE cache_key = :cache_key LIMIT 1';
    
        $query = $this->database->query($sql, [':cache_key' => $key]);
    
        return $query->fetch();
    }
    
    
    /**
     * Sets data to cache.
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        $table = self::CACHE_TABLE;
        
        $sql = "INSERT INTO $table (cache_key, cache_value) VALUES ($key, $value)";
    
        return $this->database->query($sql);
    }
    
    
    /**
     * Checks if cache record exists.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::CACHE_TABLE .  ' WHERE cache_key = :cache_key LIMIT 1';
    
        $query = $this->database->query($sql, [':cache_key' => $key]);
    
        return (bool) $query->rowCount();
    }
}

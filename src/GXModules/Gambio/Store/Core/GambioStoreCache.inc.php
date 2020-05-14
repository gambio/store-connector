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
require_once 'Exceptions/GambioStoreCacheException.inc.php';

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
    public function __construct(GambioStoreDatabase $database)
    {
        $this->database = $database;
    }
    
    
    /**
     * Returns data from cache.
     *
     * @param $key
     *
     * @return mixed | bool
     */
    public function get($key)
    {
        $sql = 'SELECT * FROM ' . self::CACHE_TABLE . ' WHERE cache_key = :cache_key LIMIT 1';
        
        $query            = $this->database->query($sql, [':cache_key' => $key]);
        $result           = $query->fetch();
        $errorInformation = $query->errorInfo();
        
        if ($errorInformation[0] === null) {
            return $result;
        }
        
        throw new GambioStoreCacheException('Could not get key: ' . $key . ' from cache table', 0,
            ['sqlError' => $errorInformation]);
    }
    
    
    /**
     * Sets data to cache.
     *
     * @param $key
     * @param $value
     *
     * @throws \GambioStoreCacheException
     */
    public function set($key, $value)
    {
        $sql = 'INSERT INTO ' . self::CACHE_TABLE . ' (cache_key,cache_value) VALUES (:cache_key, :cache_value) '
               . 'ON DUPLICATE KEY UPDATE cache_value=:cache_value ';
        
        $query   = $this->database->query($sql, ['cache_key' => $key, 'cache_value' => $value]);
        $success = $query->execute();
        
        if ($success) {
            return;
        }
        
        $errorInformation = $query->errorInfo();
        
        throw new GambioStoreCacheException('Could not set key: ' . $key . 'value: ' . $value . 'into cache table', 0,
            ['sqlError' => $errorInformation]);
    }
    
    
    /**
     * Checks if cache record exists.
     *
     * @param $key
     *
     * @return bool
     * @throws \GambioStoreCacheException
     */
    public function has($key)
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::CACHE_TABLE . ' WHERE cache_key = :cache_key LIMIT 1';
        
        $query = $this->database->query($sql, [':cache_key' => $key]);
        
        $count = (int)$query->fetchColumn();
        
        if ($count === false) {
            $errorInformation = $query->errorInfo();
            throw  new GambioStoreCacheException('Could not check if cache table has ' . $key, 0,
                ['sqlError' => $errorInformation]);
        }
        
        return $count > 0;
    }
}

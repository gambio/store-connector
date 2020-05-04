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

require 'GambioStoreDatabase.inc.php';

/**
 * Class GambioStoreCache
 *
 * Cache implementation for Gambio Store.
 *
 * Execute the downgrade script if needed.
 */
class GambioStoreCache
{
    const CACHE_TABLE = 'gambio_store_cache';
    
    private $database;
    
    public function __construct($database)
    {
        $this->database = $database;
    }
    
    public function get($name)
    {
        $sql = 'SELECT * FROM ' . self::CACHE_TABLE .  ' WHERE name = :name LIMIT 1';
    
        $query = $this->database->query($sql, [':name' => $name]);
    
        return $query->fetch();
    }
    
    public function set($name, $data)
    {
        $table = self::CACHE_TABLE;
        
        $sql = "INSERT INTO $table (name, data) VALUES ($name, $data)";
    
        return $this->database->query($sql, [':name' => $name]);
    }
    
    public function has($name)
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::CACHE_TABLE .  ' WHERE name = :name LIMIT 1';
    
        $query = $this->database->query($sql, [':name' => $name]);
    
        return (bool) $query->rowCount();
    }
}

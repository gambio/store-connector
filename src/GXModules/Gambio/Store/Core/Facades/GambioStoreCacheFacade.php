<?php
/* --------------------------------------------------------------
   GambioStoreCacheFacade.php 2020-05-15
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreCacheFacade_included')) {
        
        define('GambioStoreCacheFacade_included', true);
        
        require 'GambioStoreDatabaseFacade.php';
        require_once __DIR__ . '/../Exceptions/GambioStoreCacheException.inc.php';
        
        /**
         * Class GambioStoreCacheFacade
         *
         * Cache implementation for Gambio Store.
         *
         * Execute the downgrade script if needed.
         */
        class GambioStoreCacheFacade
        {
            /**
             * Cache table constant.
             */
            const CACHE_TABLE = 'gambio_store_cache';
            
            /**
             * @var \GambioStoreDatabaseFacade
             */
            private $database;
            
            
            /**
             * GambioStoreCache constructor.
             *
             * @param $database
             */
            public function __construct(GambioStoreDatabaseFacade $database)
            {
                $this->database = $database;
            }
            
            
            /**
             * Returns data from cache.
             *
             * @param $key
             *
             * @return mixed | bool
             * @throws \GambioStoreCacheException
             */
            public function get($key)
            {
                // Key must be a string in the database.
                $key    = (string)$key;
                $sql    = 'SELECT `cache_value` FROM ' . self::CACHE_TABLE . ' WHERE `cache_key` = :cache_key LIMIT 1';
                $query  = $this->database->query($sql, [':cache_key' => $key]);
                $result = $query->fetch();
                
                if ($result === false) {
                    throw new GambioStoreCacheException('Could not get key: ' . $key . ' from cache table', 0,
                        ['sqlError' => $query->errorInfo()]);
                }
                
                $cacheValue = $result['cache_value'];
                
                if ($cacheValue === 'false' || $cacheValue === 'true') {
                    $cacheValue = $cacheValue !== 'false';
                }
                
                return $cacheValue;
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
                // Boolean values would otherwise be a empty string
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                
                // Key must be a string in the database.
                $key     = (string)$key;
                $sql     = 'INSERT INTO ' . self::CACHE_TABLE
                           . ' (`cache_key`,`cache_value`) VALUES (:cache_key, :cache_value) '
                           . 'ON DUPLICATE KEY UPDATE `cache_value`=:cache_value ';
                $query   = $this->database->query($sql, ['cache_key' => $key, 'cache_value' => $value]);
                $success = $query->execute();
                
                if ($success) {
                    return;
                }
                
                $errorInformation = $query->errorInfo();
                
                throw new GambioStoreCacheException('Could not set key: ' . $key . 'value: ' . $value
                                                    . 'into cache table', 0, ['sqlError' => $errorInformation]);
            }
            
            
            /**
             * Checks if cache record exists.
             *
             * @param string $key
             *
             * @return bool
             * @throws \GambioStoreCacheException
             */
            public function has($key)
            {
                // Key must be a string in the database.
                $key   = (string)$key;
                $sql   = 'SELECT COUNT(*) FROM ' . self::CACHE_TABLE . ' WHERE `cache_key` = :cache_key LIMIT 1';
                $query = $this->database->query($sql, [':cache_key' => $key]);
                $count = $query->fetchColumn();
                
                if ($count === false) {
                    throw  new GambioStoreCacheException('Could not check if cache table has ' . $key, 0,
                        ['sqlError' => $query->errorInfo()]);
                }
                
                return (int)$count > 0;
            }
            
            
            /**
             * Checks if cache record exists.
             *
             * @param string $key
             *
             * @throws \GambioStoreCacheException
             */
            public function delete($key)
            {
                // Key must be a string in the database.
                $key     = (string)$key;
                $sql     = 'DELETE FROM ' . self::CACHE_TABLE . ' WHERE `cache_key` = :cache_key LIMIT 1';
                $query   = $this->database->query($sql, [':cache_key' => $key]);
                $success = $query->execute();
                
                if ($success) {
                    return;
                }
                
                throw new GambioStoreCacheException('Could not delete key: ' . $key . 'from cache table', 0,
                    ['sqlError' => $query->errorInfo()]);
            }
        }
    }
}
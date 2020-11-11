<?php
/* --------------------------------------------------------------
   GambioStoreDatabaseFacade.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreDatabaseFacade_included')) {
        
        define('GambioStoreDatabaseFacade_included', true);
        
        require 'GambioStoreFileSystemFacade.php';
        
        /**
         * Class GambioStoreDatabaseFacade
         *
         * This class encapsulates the PDO database layer provided by PHP.
         *
         * This class is the facade for the GambioStoreDatabase class.
         *
         * The class is used for migration scripts that can be run after an installation or uninstallation of a package
         * through the Store. It is not included in our Connector Core logic and has a define constant to prevent it
         * from being loaded until desired. This was introduced to ensure that during a self update of the Connector,
         * the class will have been replaced with its updated counter part before being loaded into PHP memory,
         * allowing us to execute new code during the self update.
         *
         * Functionality is implemented by duplicating methods of the original class.
         *
         * The initial check for the StoreKey_MigrationScript constant avoids automatic class auto-loading
         * by the shop's "MainFactory" since we need a unique new version during the update.
         *
         */
        class GambioStoreDatabaseFacade
        {
            /**
             * @var \GambioStoreDatabase
             */
            private static $instance;
            
            /**
             * @var \PDO
             */
            private $pdo;
            
            
            /**
             * GambioStoreDatabaseFacade constructor.
             *
             * @param \PDO $pdo
             */
            public function __construct(PDO $pdo)
            {
                $this->pdo = $pdo;
            }
            
            
            /**
             * Connects to the database and returns a class instance.
             *
             * @param \GambioStoreFileSystemFacade $fileSystem
             *
             * @return \GambioStoreDatabase|\GambioStoreDatabaseFacade
             */
            public static function connect(GambioStoreFileSystemFacade $fileSystem)
            {
                if (self::$instance === null) {
                    require_once $fileSystem->getShopDirectory() . '/admin/includes/configure.php';
                    
                    $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_DATABASE;
                    
                    $pdo = new PDO($dsn, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
                    
                    self::$instance = new GambioStoreDatabaseFacade($pdo);
                }
                
                return self::$instance;
            }
            
            
            /**
             * Performs a database query.
             *
             * Query variables can be used to escape values before being executed.
             *
             * Example:
             *
             * $database->query('SELECT * from customers WHERE customers_id = :customer_id', ['customer_id' => 1]);
             *
             * @param string $sql
             * @param array  $parameters
             *
             * @return bool|\PDOStatement
             */
            public function query($sql, array $parameters = [])
            {
                if ($parameters && !count(array_filter(array_keys($parameters), 'is_string'))) {
                    throw new RuntimeException('Parameters array should be associative.');
                }
                
                $statement = $this->pdo->prepare($sql);
                
                $statement->execute($parameters);
                
                return $statement;
            }
            
            
            /**
             * Returns the MySQL version
             *
             * @return string
             */
            public function getVersion()
            {
                return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            }
        }
    }
}

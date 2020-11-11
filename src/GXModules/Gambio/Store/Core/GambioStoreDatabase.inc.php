<?php
/* --------------------------------------------------------------
   GambioStoreDatabase.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'GambioStoreFileSystem.inc.php';

/**
 * Class GambioStoreDatabase
 *
 * This class encapsulates the PDO database layer provided by PHP.
 */
class GambioStoreDatabase
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
     * GambioStoreDatabase constructor.
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
     * @param \GambioStoreFileSystem $fileSystem
     *
     * @return \GambioStoreDatabase
     */
    public static function connect(GambioStoreFileSystem $fileSystem)
    {
        if (self::$instance === null) {
            require_once $fileSystem->getShopDirectory() . '/admin/includes/configure.php';
            
            $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_DATABASE;
            
            $pdo = new PDO($dsn, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
            
            self::$instance = new GambioStoreDatabase($pdo);
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

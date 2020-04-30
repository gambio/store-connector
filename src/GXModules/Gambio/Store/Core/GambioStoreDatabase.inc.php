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

/**
 * Class StoreDatabase
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
     * StoreDatabase constructor.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    
    /**
     * Connects to the database and returns a class instance.
     */
    public static function connect()
    {
        if (self::$instance === null) {
            require_once __DIR__ . '../../../../includes/configure.php';
            
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
            throw new \RuntimeException('Parameters array should be associative.');
        }

        $statement = $this->pdo->prepare($sql);
        
        foreach ($parameters as $key => $value) {
            $statement->bindParam($key, $value);
        }
        
        $statement->execute();
        
        return $statement;
    }
}

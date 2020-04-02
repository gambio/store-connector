<?php
/* --------------------------------------------------------------
   StoreMigration.inc.php 2020-04-02
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class StoreMigration
 *
 * This class ensures that the database is up to date and any unnecessary files are removed.
 */
class StoreMigration
{
    /**
     * @var \StoreDatabase
     */
    private $database;
    
    /**
     * Glob strings of files that are not needed any more.
     *
     * @var array
     */
    private $obsoleteFiles = [];
    
    
    /**
     * StoreMigration constructor.
     *
     * @param \StoreDatabase $database
     */
    public function __construct(\StoreDatabase $database)
    {
        $this->database = $database;
    }
    
    
    /**
     * Runs the Store related migrations.
     */
    public function run()
    {
        // TODO: Ensure the shop database is to the latest desired state. 
    }
}

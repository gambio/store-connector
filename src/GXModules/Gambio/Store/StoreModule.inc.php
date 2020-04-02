<?php
/* --------------------------------------------------------------
   StoreModule.php 2020-04-02
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require __DIR__ . '/Core/StoreCompatibility.inc.php';
require __DIR__ . '/Core/StoreDatabase.inc.php';
require __DIR__ . '/Core/StoreLogger.inc.php';
require __DIR__ . '/Core/StoreMigration.inc.php';

class StoreModule
{
    public function run()
    {
        $database = StoreDatabase::connect();
        
        $migration = new StoreMigration($database);
        
        $migration->run();
    }
}

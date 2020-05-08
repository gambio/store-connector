<?php
/* --------------------------------------------------------------
   GambioStoreMigration.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStoreMigration
 *
 * This class migrates up and down for the gambio store.
 */
class GambioStoreMigration
{
    /**
     * @var array
     */
    private $up;
    
    /**
     * @var array
     */
    private $down;
    
    
    /**
     * GambioStoreMigration constructor.
     *
     * @param array $up
     * @param array $down
     */
    public function __construct(array $up, array $down)
    {
        $this->up   = $up;
        $this->down = $down;
    }
    
    
    /**
     * Migrate up.
     *
     * @throws \Exception
     */
    public function up()
    {
        foreach ($this->up as $item) {
            try {
                require_once $item;
            } catch (Exception $exception) {
                throw new UpMigrationException('Up migration failed. File: ', 0, $item);
            }
        }
    }
    
    
    /**
     * Migrate down.
     *
     * @throws \Exception
     */
    public function down()
    {
        foreach ($this->down as $item) {
            try {
                require_once $item;
            } catch (Exception $exception) {
                throw new DownMigrationException('Down migration failed. File: ', 0, $item);
            }
        }
    }
}
<?php
/* --------------------------------------------------------------
   GambioStoreController.inc.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require __DIR__ . '../../GambioStoreConnector.inc.php';

class GambioStoreController extends AdminHttpViewController
{
    /**
     *
     */
    private $gambioStoreConnector;
    
    /**
     *
     */
    private $gambioStoreConfiguration;
    
    
    /**
     *
     */
    private function setup()
    {
        $this->gambioStoreConnector = GambioStoreConnector::getInstance();
        $this->gambioStoreConfiguration = $this->gambioStoreConnector->getConfiguration();
    }
}

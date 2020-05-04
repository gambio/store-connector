<?php
/* --------------------------------------------------------------
   GambioStoreInstallation.inc.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
require './Abstract/AbstractGambioStoreFileSystem.inc.php';

/**
 * Class StoreInstallation
 *
 * Performs a Store package installation and take care of all the required actions.
 *
 * Execute the upgrade script if needed.
 */
class GambioStoreInstallation extends AbstractGambioStoreFileSystem
{
    private $token;
    
    private $cache;
    
    private $fileList;
    
    
    public function __construct($fileList, $token, $cache)
    {
        $this->fileList = $fileList;
        $this->token = $token;
        $this->cache = $cache;
    }
    
    
    public function perform($data, $name)
    {
        
    }
}

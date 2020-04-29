<?php
/* --------------------------------------------------------------
   GambioStoreCompatibility.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class StoreCompatibility
 *
 * This class allows to code to check for certain shop resources or features.
 *
 * Example:
 *
 * $storeCompatibility->has(StoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE); // returns true or false
 */
class GambioStoreCompatibility
{
    const RESOURCE_GM_CONFIGURATION_TABLE = 'gm_configuration';
    
    
    public function has($resource)
    {
        
    }
}

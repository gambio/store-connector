<?php
/* --------------------------------------------------------------
   gambio_store.php 2020-04-02
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require __DIR__ . 'GXModules/Gambio/Store/StoreModule.inc.php';

$storeModule = new StoreModule();

$storeModule->run(); 

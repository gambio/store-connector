<?php
/* --------------------------------------------------------------
   update.php 2020-05-14
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../GambioStoreConnector.inc.php';

$connector = GambioStoreConnector::getInstance();
$connector->update();
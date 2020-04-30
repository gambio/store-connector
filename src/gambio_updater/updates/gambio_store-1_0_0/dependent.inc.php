<?php

$menuPath = __DIR__ . '/../../../system/conf/admin_menu/gambio_menu.xml';

$menuContent = file_get_contents($menuPath);

$gambioMenuRegex = '/.<menugroup id="BOX_HEADING_GAMBIO_STORE.*\s.*\s.*\s.*\s.<\/menugroup>/i';

$menuContent = preg_replace($gambioMenuRegex, '', $menuContent);

file_put_contents($menuPath, $menuContent);


<?php

require_once __DIR__ . '/../../../GXModules/Gambio/Store/GambioStoreConnector.inc.php';

$menuPath = __DIR__ . '/../../../system/conf/admin_menu/gambio_menu.xml';

$menuContent = file_get_contents($menuPath);

$gambioMenuRegex = '/.<menugroup id="BOX_HEADING_GAMBIO_STORE.*\s.*\s.*\s.*\s.<\/menugroup>/i';

$menuContent = preg_replace($gambioMenuRegex, '', $menuContent);

file_put_contents($menuPath, $menuContent);

$connector = GambioStoreConnector::getInstance();

$configuration = $connector->getConfiguration();

if (!$configuration->has('GAMBIO_STORE_URL')) {
    $configuration->create('GAMBIO_STORE_URL', 'https://store.gambio.com/a');
}

if (!$configuration->has('GAMBIO_STORE_TOKEN')) {
    $gambioToken = $connector->generateToken();
    $configuration->create('GAMBIO_STORE_TOKEN', $gambioToken);
}

if (!$configuration->has('GAMBIO_STORE_IS_REGISTERED')) {
    $configuration->create('GAMBIO_STORE_IS_REGISTERED', 'false');
}

if (!$configuration->has('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING')) {
    if ($configuration->has('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING')) {
        $value = $configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING');
        $configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', $value);
    } else {
        $configuration->create('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', 'false');
    }
}
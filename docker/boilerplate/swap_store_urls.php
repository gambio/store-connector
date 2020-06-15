<?php

$dsn = 'mysql:host=hub-connector-mysql-{{branch}};dbname=gxdev;charset=utf8';

$user = 'gxdev';
$pass = 'gxdev';

$pdo = new \PDO($dsn, $user, $pass);

$pdo->query("UPDATE configuration SET configuration_value='http://" . $_ENV['DOCKER_HOST_IP'] . ":6100/public/api.php/api/v1' WHERE configuration_key='MODULE_PAYMENT_GAMBIO_HUB_URL'");

$pdo->query("UPDATE configuration SET configuration_value='http://" . $_ENV['DOCKER_HOST_IP'] . ":6300/' WHERE configuration_key='MODULE_PAYMENT_GAMBIO_HUB_SETTINGS_APP_URL'");

$pdo->query("UPDATE configuration SET configuration_value='http://" . $_ENV['DOCKER_HOST_IP'] . ":6400/' WHERE configuration_key='MODULE_PAYMENT_GAMBIO_HUB_ACCOUNT_APP_URL'");

$pdo->query("UPDATE configuration SET configuration_value='https://core-api.gambiohub.com/trust/hub_hosts.json_' WHERE configuration_key='MODULE_PAYMENT_GAMBIO_HUB_IP_LIST_URL'");

$pdo->query("UPDATE configuration SET configuration_value='https://core-api.gambiohub.com/trust/rest_actions.json_' WHERE configuration_key='MODULE_PAYMENT_GAMBIO_HUB_REST_ACTIONS_URL'");

echo "Success";

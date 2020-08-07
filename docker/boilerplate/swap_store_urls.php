<?php

$dsn = 'mysql:host=store-connector-mysql-{{branch}};dbname=gxdev;charset=utf8';

$user = 'gxdev';
$pass = 'gxdev';

$pdo = new \PDO($dsn, $user, $pass);

$pdo->query('UPDATE gm_configuration SET gm_value="http://' . $_ENV['DOCKER_HOST_IP'] . ':6900" WHERE gm_key="GAMBIO_STORE_URL"');

$pdo->query('UPDATE gx_configuration SET gm_value="http://' . $_ENV['DOCKER_HOST_IP'] . ':6900" WHERE gm_key="GAMBIO_STORE_URL"');

$pdo->query('UPDATE gm_configuration SET gm_value="http://' . $_ENV['DOCKER_HOST_IP'] . ':6800" WHERE gm_key="APP_STORE_URL"');

$pdo->query('UPDATE gx_configuration SET gm_value="http://' . $_ENV['DOCKER_HOST_IP'] . ':6800" WHERE gm_key="APP_STORE_URL"');

echo "Success";

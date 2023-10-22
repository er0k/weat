<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$config = new Weat\Config();
$locator = new GeoIp2\Database\Reader($config->city_db);
$weat = new Weat($config, $locator);

try {
    echo $weat->run();
} catch (Weat\Exception $e) {
    echo $e->getMessage();
    error_log($e);
}

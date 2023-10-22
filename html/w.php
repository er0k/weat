<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$config = new Weat\Config();
$locator = new GeoIp2\Database\Reader($config->city_db);
$store = new SleekDB\Store('weat', $config->store, $config->store_config);
$weat = new Weat($config, $locator, $store);

try {
    echo $weat->run();
} catch (Weat\Exception $e) {
    echo "<h2>{$e->getMessage()}</h2>";
    error_log($e);
}

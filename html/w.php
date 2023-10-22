<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$config = new Weat\Config();
$weat = new Weat($config);

try {
    echo $weat->run();
} catch (Weat\Exception $e) {
    echo $e->getMessage();
    error_log($e);
}

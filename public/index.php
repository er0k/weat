<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$weat = new Weat();

try {
    $weat->run();
} catch (Weat\Exception $e) {
    echo "<pre>$e</pre>";
}
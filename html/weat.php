<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

try {
    echo (new Weat())->run();
} catch (Weat\Exception $e) {
    echo $e->getMessage();
    error_log($e);
}

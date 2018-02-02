<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$config = new Weat\Config();

$locator = new GeoIp2\Database\Reader($config->city_db);

$loader = new Twig_Loader_Filesystem($config->twig['template_dir']);
$template = new Twig_Environment(
    new Twig_Loader_Filesystem($config->twig['template_dir']),
    $config->twig['options']
);
$template->addExtension(new Twig_Extensions_Extension_Intl());

$weat = new Weat($config, $locator, $template);

try {
    echo $weat->run();
} catch (Weat\Exception $e) {
    echo "<h2>{$e->getMessage()}</h2>";
    if ($config->show_debug) {
        echo "<pre>$e</pre>";
    }
}
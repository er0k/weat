<?php

use GeoIp2\Database\Reader;
use Weat\Config;
use Weat\Location;
use Weat\Exception;
use Weat\WeatherService;

class Weat
{
    /** @var Config */
    private $config;

    /** @var Reader */
    private $reader;

    /** @var Twig_Environment */
    private $twig;

    public function __construct()
    {
        $this->config = new Config();
        $this->reader = new Reader($this->config->city_db);
        $this->twig = $this->getTwig();
    }

    public function run()
    {
        $location = $this->getLocation();
        // print_r($location);

        $wg = new WeatherService($this->config, 'wunderground');
        $weather = $wg->getWeather($location);

        // print_r($weather);

        echo $this->twig->render('index.html', array('weather' => $weather));
    }

    /**
     * @return Location
     * @throws Exception
     */
    private function getLocation()
    {
        $ip = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];

        $location = new Location();

        $location->ip = $ip;

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) ) {
            return $location;
        }

        $record = $this->reader->city($ip);

        $location->country = $record->country->name;
        $location->city = $record->city->name;
        $location->state = $record->mostSpecificSubdivision->name;
        $location->zip = $record->postal->code;
        $location->lat = $record->location->latitude;
        $location->lon = $record->location->longitude;

        return $location;
    }

    private function getTwig()
    {
        $loader = new Twig_Loader_Filesystem($this->config->template_dir);
        $twig = new Twig_Environment($loader, array());

        return $twig;
    }
}
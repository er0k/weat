<?php

use GeoIp2\Database\Reader;
use Weat\Config;
use Weat\Location;
use Weat\Exception;
use Weat\Sun;
use Weat\Weather;
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
        $this->twig = $this->getTwig($this->config->twig);
    }

    public function run()
    {
        $location = $this->getLocation();
        // print_r($location);

        $wg = new WeatherService($this->config, 'wunderground');
        $weather = $wg->getWeather($location);
        // print_r('weather');

        $sun = $this->getSunTimes($location, $weather);
        // print_r($sun);

        echo $this->twig->render('index.html', array(
            'location' => $location,
            'weather' => $weather,
            'sun' => $sun,
        ));
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
            // if it's a local IP, no need to try and geolocate
            return $location;
        }

        $record = $this->reader->city($ip);

        $location->country = $record->country->name;
        $location->city = $record->city->name;
        $location->state = $record->mostSpecificSubdivision->name;
        $location->zip = $record->postal->code;
        $location->lat = $record->location->latitude;
        $location->lon = $record->location->longitude;
        $location->timezone = $record->location->timeZone;

        return $location;
    }

    /**
     * @param  Location $location
     * @param  Weather $weather
     * @return Sun
     */
    private function getSunTimes(Location $location, Weather $weather)
    {
        // prefer to calculate sun times from location data
        // but fall back on weather data
        $timezone = $location->timezone ? $location->timezone : $weather->timezone;
        $lat = $location->lat ? $location->lat : $weather->lat;
        $lon = $location->lon ? $location->lon : $weather->lon;
        $epoch = $location->lat ? time() : $weather->epoch;
        $timeString = $location->lat ? 'now' : $weather->timeRfc;

        if (!$timezone) {
            throw new Exception("could not determine timezone");
        }

        $dtz = new \DateTimeZone($timezone);
        $dt = new \DateTime($timeString, $dtz);
        $offsetInSeconds = $dtz->getOffset($dt);
        $offset = $offsetInSeconds / 60 / 60;
        // sunrise or sunset is defined to occur when the geometric zenith
        // distance of center of the Sun is 90.8333 degrees. That is, the center
        // of the Sun is geometrically 50 arcminutes below a horizontal plane.
        // For an observer at sea level with a level, unobstructed horizon,
        // under average atmospheric conditions, the upper limb of the Sun will
        // then appear to be tangent to the horizon. The 50-arcminute geometric
        // depression of the Sun's center used for the computations is obtained
        // by adding the average apparent radius of the Sun (16 arcminutes) to
        // the average amount of atmospheric refraction at the horizon
        // (34 arcminutes).
        $zenith = 90 + (50 / 60);

        $sun = new Sun();

        $sunrise = date_sunrise($epoch, SUNFUNCS_RET_STRING, $lat, $lon, $zenith, $offset);
        $sunset = date_sunset($epoch, SUNFUNCS_RET_STRING, $lat, $lon, $zenith, $offset);

        $sun->rise = new \DateTime($sunrise, $dtz);
        $sun->set = new \DateTime($sunset, $dtz);

        $now = new \DateTime('now', $dtz);

        $sun->riseDiff = $now->diff($sun->rise);
        $sun->setDiff = $now->diff($sun->set);

        return $sun;
    }

    /**
     * @param  array $twigConfig
     * @return Twig_Environment
     */
    private function getTwig(array $twigConfig)
    {
        $loader = new Twig_Loader_Filesystem($twigConfig['template_dir']);
        $twig = new Twig_Environment($loader, $twigConfig['options']);
        $twig->addExtension(new Twig_Extensions_Extension_Intl());

        return $twig;
    }
}
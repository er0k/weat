<?php

use GeoIp2\Database\Reader as Locator;
use Twig_Environment as Template;
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

    /** @var Locator */
    private $locator;

    /** @var Template */
    private $template;

    public function __construct(Config $config, Locator $locator, Template $template)
    {
        $this->config = $config;
        $this->locator = $locator;
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function run()
    {
        $service = $this->getService();

        $location = $this->getLocation();

        $wg = new WeatherService($this->config, $service);
        $weather = $wg->getWeather($location);

        $sun = $this->getSunTimes($location, $weather);

        $debug = [
            'info' => $this->config->getDebugInfo(),
            'show' => $this->config->show_debug,
        ];

        return $this->template->render('index.html', [
            'location' => $location,
            'weather' => $weather,
            'sun' => $sun,
            'debug' => $debug
        ]);
    }

    private function getService()
    {
        $service = isset($_GET['s']) ? $_GET['s'] : WeatherService::WEATHER_UNDERGROUND;

        return $service;
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
            $this->config->debug('local IP, using default location');
            return $this->getDefaultLocation($location);
        }

        try {
            $record = $this->locator->city($ip);
        } catch (AddressNotFoundException $e) {
            throw new Exception("Could not geolocate your IP", 1, $e);
        } catch (InvalidDatabaseException $e) {
            throw new Exception("Could not read geolocation database", 2, $e);
        }

        $location->country = $record->country->name;
        $location->city = $record->city->name;
        $location->state = $record->mostSpecificSubdivision->name;
        $location->zip = $record->postal->code;
        $location->lat = $record->location->latitude;
        $location->lon = $record->location->longitude;
        $location->timezone = $record->location->timeZone;

        return $location;
    }

    private function getDefaultLocation(Location $location)
    {
        $defaultLocation = $this->config->default_location;

        $location->country = $defaultLocation['country'];
        $location->city = $defaultLocation['city'];
        $location->state = $defaultLocation['state'];
        $location->zip = $defaultLocation['zip'];
        $location->lat = $defaultLocation['lat'];
        $location->lon = $defaultLocation['lon'];
        $location->timezone = $defaultLocation['timezone'];

        return $location;
    }

    /**
     * @param  Location $location
     * @param  Weather $weather
     * @return Sun
     */
    private function getSunTimes(Location $location, Weather $weather)
    {
        $sun = new Sun();

        // prefer to calculate sun times from location data
        // but fall back on weather data
        $timezone = $location->timezone ? $location->timezone : $weather->timezone;

        if (!$timezone) {
            return $sun;
        }

        $lat = $location->lat ? $location->lat : $weather->lat;
        $lon = $location->lon ? $location->lon : $weather->lon;
        $epoch = $location->lat ? time() : $weather->epoch;
        $timeString = $location->lat ? 'now' : $weather->timeRfc;

        $dateTimeZone= new \DateTimeZone($timezone);
        $dateTime = new \DateTime($timeString, $dateTimeZone);
        $offsetInSeconds = $dateTimeZone->getOffset($dateTime);
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

        $sun->rise = new \DateTime($sunrise, $dateTimeZone);
        $sun->set = new \DateTime($sunset, $dateTimeZone);

        $now = new \DateTime('now', $dateTimeZone);

        $sun->riseDiff = $now->diff($sun->rise);
        $sun->setDiff = $now->diff($sun->set);

        return $sun;
    }

}

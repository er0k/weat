<?php

use Weat\Config;
use Weat\Location;
use Weat\Locator;
use Weat\Exception;
use Weat\Receiver;
use Weat\Sun;
use Weat\Weather;
use Weat\WeatherService;

class Weat
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run(): ?string
    {
        if (!$this->isServiceRequested()) {
            (new Receiver($this->config))->save();
            return null;
        }

        $location = (new Locator($this->config))->getLocation();

        $weather = (new WeatherService(
            $this->config,
            $this->getService()
        ))->getWeather($location);

        $sun = $this->getSunTimes($location, $weather);

        $output = [
            'location' => $location,
            'weather' => $weather,
            'sun' => $sun,
        ];

        return $this->sendJson($output);
    }

    private function isServiceRequested(): bool
    {
        if (isset($_GET['s'])) {
            return true;
        }

        return false;
    }

    private function getService(): int
    {
        return $_GET['s'];
    }

    private function getSunTimes(Location $location, Weather $weather): Sun
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

    private function sendJson(array $out): string
    {
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($out);
    }

}

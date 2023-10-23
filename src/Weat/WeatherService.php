<?php

namespace Weat;

use Weat\Exception;
use Weat\WeatherService\AbstractWeatherService;

class WeatherService
{
    const TYPES = [
        'LOCAL' => 0,
        'WEATHER_UNDERGROUND' => 1,
        'OPEN_WEATHER_MAP' => 2,
        'NOAA' => 3,
        'DARK_SKY' => 4,
    ];

    const ACTIVE_TYPES = [
        ['id' => 0, 'name' => 'Local'],
        ['id' => 2, 'name' => 'OpenWeatherMap'],
        ['id' => 3, 'name' => 'NOAA'],
    ];

    private $service;

    public function __construct(Config $config, int $service)
    {
        $this->service = $this->getService($config, $service);
    }

    public function getWeather(Location $location): Weather
    {
        return $this->service->getWeather($location);
    }

    private function getService(Config $config, int $service): AbstractWeatherService
    {
        switch ($service) {
            case self::TYPES['WEATHER_UNDERGROUND']:
                // return new WeatherService\WeatherUnderground($config);
                $this->gone("RIP wunderground");
            case self::TYPES['OPEN_WEATHER_MAP']:
                return new WeatherService\OpenWeatherMap($config);
            case self::TYPES['NOAA']:
                return new WeatherService\NOAA($config);
            case self::TYPES['DARK_SKY']:
                // return new WeatherService\DarkSky($config);
                $this->gone("RIP darksky");
            case self::TYPES['LOCAL']:
                // This is pretty hacky, but it makes sure we aren't caching data
                // from the local weather service. Caching is really only useful
                // when we get data from weather APIs. Eventually the makeshift
                // caching will be replaced by a proper WeatherStorage class
                $_GET['nocache'] = true;
                return new WeatherService\Local($config);
            default:
                throw new Exception("uknown weather service: {$service}");
        }
    }

    private function gone(string $msg)
    {
        http_response_code(410);
        die($msg);
    }
}

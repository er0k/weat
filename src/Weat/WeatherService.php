<?php

namespace Weat;

use Weat\WeatherService\AbstractWeatherService;

class WeatherService
{
    const TYPES = [
        'WEATHER_UNDERGROUND' => 1,
        'OPEN_WEATHER_MAP' => 2,
        'NOAA' => 3,
        'DARK_SKY' => 4,
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
                return new WeatherService\WeatherUnderground($config);
            case self::TYPES['OPEN_WEATHER_MAP']:
                return new WeatherService\OpenWeatherMap($config);
            case self::TYPES['NOAA']:
                return new WeatherService\NOAA($config);
            case self::TYPES['DARK_SKY']:
                return new WeatherService\DarkSky($config);;
            default:
                throw new Exception("uknown weather service: {$service}");
        }
    }


}

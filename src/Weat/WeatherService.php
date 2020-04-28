<?php

namespace Weat;

class WeatherService
{
    const TYPES = [
        'WEATHER_UNDERGROUND' => 1,
        'OPEN_WEATHER_MAP' => 2,
        'NOAA' => 3,
        'DARK_SKY' => 4,
    ];

    private $service;

    /**
     * @param Config $config
     * @param int $service
     */
    public function __construct(Config $config, $service)
    {
        $this->service = $this->getService($config, $service);
        $config->debug('using weather service: ' . get_class($this->service));
    }

    /**
     * @param  Location $location
     * @return Weather
     */
    public function getWeather(Location $location)
    {
        return $this->service->getWeather($location);
    }

    /**
     * @param  Config $config
     * @param  int $service
     * @return AbstractWeatherService
     */
    private function getService(Config $config, $service)
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

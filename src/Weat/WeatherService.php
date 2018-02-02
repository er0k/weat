<?php

namespace Weat;

class WeatherService
{
    const WEATHER_UNDERGROUND = 1;
    const OPEN_WEATHER_MAP = 2;
    const NOAA = 3;
    const DARK_SKY = 4;

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
            case self::WEATHER_UNDERGROUND:
                return new WeatherService\WeatherUnderground($config);
            case self::OPEN_WEATHER_MAP:
                return new WeatherService\OpenWeatherMap($config);
            case self::NOAA:
                return new WeatherService\NOAA($config);
            case self::DARK_SKY:
                return new WeatherService\DarkSky($config);;
            default:
                throw new Exception("uknown weather service: {$service}");
        }
    }


}

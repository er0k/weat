<?php

namespace Weat;

class WeatherService
{
    const WEATHER_UNDERGROUND = 1;
    const ACCUWEATHER = 2;
    const OPEN_WEATHER_MAP = 3;
    const NOAA = 4;
    const FORECAST_IO = 5;

    private $service;

    /**
     * @param Config $config
     * @param int $service
     */
    public function __construct(Config $config, $service = self::WEATHER_UNDERGROUND)
    {
        $this->service = $this->getService($config, $service);
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
            case self::ACCUWEATHER:
                return new WeatherService\AccuWeather($config);
            case self::OPEN_WEATHER_MAP:
                return new WeatherService\OpenWeatherMap($config);
            case self::NOAA:
                return new WeatherService\NOAA($config);
            case self::FORECAST_IO:
                return new WeatherService\ForecastIO($config);;
            default:
                throw new Exception("uknown weather service");
        }
    }


}
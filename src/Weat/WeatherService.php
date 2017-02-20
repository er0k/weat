<?php

namespace Weat;

class WeatherService
{
    private $service;

    /**
     * @param Config $config
     * @param string $service
     */
    public function __construct(Config $config, $service = '')
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

    private function getService(Config $config, $service)
    {
        switch ($service) {
            case 'wunderground':
                return new WeatherService\WeatherUnderground($config);
            case 'accuweather':
                return new WeatherService\AccuWeather($config);
            case 'openweathermap':
                return new WeatherService\OpenWeatherMap($config);
            case 'noaa':
                return new WeatherService\NOAA($config);
            case 'forecastio':
                return new WeatherService\ForecastIO($config);;
            default:
                throw new Exception("uknown weather service");
        }
    }


}
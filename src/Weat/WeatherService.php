<?php

namespace Weat;

class WeatherService
{
    private $service;

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
        $this->service->setLocation($location);

        return $this->service->getWeather();
    }

    private function getService(Config $config, $service)
    {
        switch ($service) {
            case 'wunderground':
                echo 'weather underground service requested!' . "\n";
                return new WeatherService\WeatherUnderground($config);
            case 'accuweather':
                return new WeatherService\AccuWeather;
            case 'openweathermap':
                return new WeatherService\OpenWeatherMap;
            case 'noaa':
                return new WeatherService\NOAA;
            case 'forecastio':
                return new WeatherService\ForecastIO;
            default:
                throw new Exception("uknown weather service");
        }
    }


}
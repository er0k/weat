<?php

namespace Weat\WeatherService;

use Weat\Exception;
use Weat\Location;
use Weat\Weather;

/**
 * @todo stub
 */
class AccuWeather extends AbstractWeatherService
{
    /**
     * @param  Location $location
     * @return \stdClass
     */
    protected function getWeatherDataFromApi(Location $location)
    {
        $jsonData = json_encode(array('test' => 'test'));

        $data = json_decode($jsonData);

        return $data;
    }

    /**
     * @param  Weather $weather
     * @param  \stdClass $data
     * @return Weather
     */
    protected function hydrate(Weather $weather, \stdClass $data)
    {
        return $weather;
    }
}
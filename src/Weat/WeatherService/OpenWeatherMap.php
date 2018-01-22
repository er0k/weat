<?php

namespace Weat\WeatherService;

use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class OpenWeatherMap extends AbstractWeatherService
{
    /**
     * @param  Location $location
     * @return \stdClass
     */
    protected function getWeatherDataFromApi(Location $location)
    {
        $key = $this->getKey();

        $query = $this->getQuery($location);

        // current conditions
        $url = "http://api.openweathermap.org/data/2.5/weather$query&units=imperial&APPID=$key";
        // forecast
        $url2 = "http://api.openweathermap.org/data/2.5/forecast$query&units=imperial&APPID=$key";

        $this->config->debug($url);

        $jsonData = file_get_contents($url);
        if ($jsonData === false) {
            throw new Exception("could not get open weather map API data");
        }

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
        #print_r($data);

        $weather->current = $data->weather[0]->main;
        $weather->currentTemp = $data->main->temp;
        $weather->currentIcon = 'https://openweathermap.org/img/w/' . $data->weather[0]->icon . '.png';
        $weather->pressure = $this->getPressureDifference($data->main->pressure) . 'mb';
        $weather->humidity = $data->main->humidity . '%';
        $weather->wind = $data->wind->speed . 'MPH at ' . $data->wind->deg . ' degrees';

        return $weather;
    }

    private function getKey()
    {
        return $this->config->open_weather_map_key;
    }

    private function getQuery(Location $location)
    {

        if (!empty($location->lat) && !empty($location->lon)) {
            return sprintf("?lat=%s&lon=%s", $location->lat, $location->lon);
        }

        if (!empty($location->zip)) {
            return sprintf("?zip=%s,us", $location->zip);
        }

        throw new Exception("cannot query open weather map with insufficient location data");
    }
}

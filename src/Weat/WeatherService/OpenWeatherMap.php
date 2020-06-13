<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class OpenWeatherMap extends AbstractWeatherService
{
    const URL = 'http://api.openweathermap.org/data/2.5/weather%s&units=imperial&APPID=%s';
    const FORECAST_URL = 'http://api.openweathermap.org/data/2.5/forecast%s&units=imperial&APPID=%s';

    protected function getWeatherDataFromApi(Location $location): stdClass
    {
        $key = $this->config->open_weather_map_key;

        $query = $this->getQuery($location);

        // current conditions
        $url = sprintf(self::URL, $query, $key);
        // forecast
        $urlForecast = sprintf(self::FORECAST_URL, $query, $key);

        $this->config->debug($url);

        $jsonData = file_get_contents($url);
        if ($jsonData === false) {
            throw new Exception("could not get open weather map API data");
        }

        $data = json_decode($jsonData);

        return $data;
    }

    protected function hydrate(Weather $weather, \stdClass $data): Weather
    {
        $this->config->debug(print_r($data, true));

        $weatherConditions = [];
        foreach ($data->weather as $condition) {
            $weatherConditions[] = $condition->description;
        }
        $weather->current = implode(', ', $weatherConditions);
        $weather->currentTemp = $data->main->temp;
        $weather->currentIcon = 'https://openweathermap.org/img/w/' . $data->weather[0]->icon . '.png';
        $weather->pressure = $this->getPressureDifference($data->main->pressure) . 'mb';
        $weather->humidity = $data->main->humidity . '%';
        $weather->visibility = $this->metersToMiles($data->visibility) . ' miles'; // meters?
        $weather->clouds = $data->clouds->all . '%';

        $windSpeed = number_format($data->wind->speed, 1);
        $windDirection = $this->degreesToDirection($data->wind->deg);
        // $weather->wind = $data->wind->speed . 'MPH at ' . $data->wind->deg . ' degrees';
        $weather->wind = "From the $windDirection at $windSpeed MPH";

        return $weather;
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

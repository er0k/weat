<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;
use Weat\WeatherService;

class OpenWeatherMap extends AbstractWeatherService
{
    const TYPE = WeatherService::TYPES['OPEN_WEATHER_MAP'];

    const URL = 'http://api.openweathermap.org/data/2.5/weather%s&units=imperial&APPID=%s';
    const FORECAST_URL = 'http://api.openweathermap.org/data/2.5/forecast%s&units=imperial&APPID=%s';

    protected function getWeatherDataFromService(Location $location): stdClass
    {
        $key = $this->config->open_weather_map_key;

        $query = $this->getQuery($location);

        // current conditions
        $url = sprintf(self::URL, $query, $key);
        // forecast
        $urlForecast = sprintf(self::FORECAST_URL, $query, $key);

        return $this->request($url);
    }

    protected function hydrate(Weather $weather, \stdClass $data): Weather
    {
        // print_r($data);

        $weatherConditions = [];
        foreach ($data->weather as $condition) {
            $weatherConditions[] = $condition->description;
        }
        $weather->current = implode(', ', $weatherConditions);
        $weather->currentTemp = $data->main->temp;
        $weather->currentIcon = 'https://openweathermap.org/img/w/' . $data->weather[0]->icon . '.png';
        $weather->precipitationHourly = $data->rain->{'1h'} ?? 0;
        $weather->pressure = $this->getPressureDifference($data->main->pressure);
        $weather->humidity = $data->main->humidity;
        $weather->visibility = $this->metersToMiles($data->visibility) . ' miles'; // meters?
        $weather->clouds = $data->clouds->all . '%';

        $windSpeed = number_format($data->wind->speed, 1);
        $windDirection = $this->degreesToDirection($data->wind->deg);
        // $weather->wind = $data->wind->speed . 'MPH at ' . $data->wind->deg . ' degrees';
        $weather->wind = "From the $windDirection at $windSpeed MPH";

        $weather->location = $data->name;
        $weather->tempMin = $data->main->temp_min;
        $weather->tempMax = $data->main->temp_max;
        $weather->feelsLike = $data->main->feels_like;

        $weather->isCached = $data->isCached;

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

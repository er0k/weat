<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;
use Weat\WeatherService;

class NOAA extends AbstractWeatherService
{
    const TYPE = WeatherService::TYPES['NOAA'];

    const META_URL = 'https://api.weather.gov/points/%.4f,%.4f';
    const CURRENT_URL = 'https://api.weather.gov/stations/%s/observations/latest';

    /**
     * @link https://www.weather.gov/documentation/services-web-api
     * @param  Location $location
     * @throws Exception
     * @return \stdClass
     */
    protected function getWeatherDataFromService(Location $location): stdClass
    {

        $urlMeta = sprintf(self::META_URL, $location->lat, $location->lon);

        $data = new \stdClass();

        $metadata = $this->request($urlMeta);

        $data->urlForecast = $metadata->properties->forecast;
        $data->urlHourlyForecast = $metadata->properties->forecastHourly;
        $data->urlStations = $metadata->properties->observationStations;

        $data->closestStations = $this->getThreeClosestStations($data->urlStations);

        $data->current = $this->getCurrentObservation($data->closestStations[0]->id);

        return $data;

    }

    protected function hydrate(Weather $weather, stdClass $data): Weather
    {
        // print_r($data);

        $current = $data->current->properties;

        $date = new \DateTime($current->timestamp);
        $tz = new \DateTimeZone($this->config->default_location['timezone']);
        $date->setTimezone($tz);
        $weather->timeFriendly = 'Last Updated on ' . $date->format('F j, g:i A T');

        $weather->currentIcon = $current->icon;
        $weather->location = $data->closestStations[0]->name;
        $weather->current = $current->textDescription;;
        $weather->currentTemp = number_format($this->celsiusToFahrenheit($current->temperature->value), 1);
        $weather->humidity = round($current->relativeHumidity->value);
        $weather->dewPoint = $this->celsiusToFahrenheit($current->dewpoint->value);
        // $weather->dewPoint = $this->getDewPoint($weather->currentTemp, $weather->humidity);
        $weather->visibility = number_format($this->metersToMiles($current->visibility->value), 1) . ' miles';
        $weather->precipitation = number_format((int) $current->precipitationLast6Hours->value, 2) . ' inches';
        $weather->precipitationHourly = $this->millimetersToInches(floatval($current->precipitationLastHour->value));

        // $windSpeed = number_format($this->metersPerSecondToMilesPerHour($current->windSpeed->value), 1);
        $windSpeed = number_format($current->windSpeed->value, 1);
        $windDirection = $this->degreesToDirection((int) $current->windDirection->value);
        $weather->wind = "From the $windDirection at $windSpeed MPH";
        $weather->windChill = $this->celsiusToFahrenheit($current->windChill->value);

        $pressure = $this->pascalToMillibar(floatval($current->barometricPressure->value));
        $weather->pressure = $this->getPressureDifference($pressure);

        // var_dump($current);

        $weather->lat = $data->current->geometry->coordinates[1];
        $weather->lon = $data->current->geometry->coordinates[0];
        $weather->elevation = $this->metersToFeet($current->elevation->value);

        $weather->isCached = $data->isCached;

        return $weather;
    }

    private function getThreeClosestStations($url)
    {
        $stations = $this->request($url);

        $closestStations = [];

        foreach ($stations->features as $stationInfo) {
            $station = new \stdClass();
            $station->id = $stationInfo->properties->stationIdentifier;
            $station->name = $stationInfo->properties->name;

            $closestStations[] = $station;

            if (count($closestStations) >= 3 ) {
                break;
            }
        }

        return $closestStations;
    }

    private function getCurrentObservation($stationId)
    {
        $urlCurrent = sprintf(self::CURRENT_URL, $stationId);

        $current = $this->request($urlCurrent);

        return $current;
    }

}

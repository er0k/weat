<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class NOAA extends AbstractWeatherService
{

    const META_URL = 'https://api.weather.gov/points/%.4f,%.4f';
    const CURRENT_URL = 'https://api.weather.gov/stations/%s/observations/current';

    /**
     * @link https://www.weather.gov/documentation/services-web-api
     * @param  Location $location
     * @throws Exception
     * @return \stdClass
     */
    protected function getWeatherDataFromApi(Location $location): stdClass
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
        $current = $data->current->properties;

        $date = new \DateTime($current->timestamp);
        $tz = new \DateTimeZone($this->config->default_location['timezone']);
        $date->setTimezone($tz);
        $weather->timeFriendly = 'Last Updated on ' . $date->format('F j, g:i A T');

        $weather->currentIcon = $current->icon;
        $weather->location = $data->closestStations[0]->name;
        $weather->current = $current->textDescription;;
        $weather->currentTemp = number_format($this->celsiusToFahrenheit($current->temperature->value), 1);
        $weather->dewpoint = $current->dewpoint->value;
        $weather->humidity = round($current->relativeHumidity->value);
        $weather->visibility = number_format($this->metersToMiles($current->visibility->value), 1) . ' miles';
        $weather->precipitation = number_format((int) $current->precipitationLast6Hours->value, 2) . ' inches';

        // $windSpeed = number_format($this->metersPerSecondToMilesPerHour($current->windSpeed->value), 1);
        $windSpeed = number_format($current->windSpeed->value, 1);
        $windDirection = $this->degreesToDirection((int) $current->windDirection->value);
        $weather->wind = "From the $windDirection at $windSpeed MPH";

        $pressure = $this->pascalToMillibar(floatval($current->barometricPressure->value));
        $weather->pressure = $this->getPressureDifference($pressure);

        // var_dump($current);

        $weather->lat = $data->current->geometry->coordinates[1];
        $weather->lon = $data->current->geometry->coordinates[0];

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

    /**
     * @throws Exception
     */
    private function request(string $url): stdClass
    {
        error_log("requesting $url");
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'r0k/weat',
        ];
        curl_setopt_array($ch, $options);
        if (!$response = curl_exec($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }
        curl_close($ch);

        return json_decode($response);
    }

}

<?php

namespace Weat\WeatherService;

use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class NOAA extends AbstractWeatherService
{

    /**
     * @param  Location $location
     * @return \stdClass
     */
    protected function getWeatherDataFromApi(Location $location)
    {
        $endpoint = $this->getEndpoint($location);
        $key = $this->getKey();

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => sprintf('https://www.ncdc.noaa.gov/cdo-web/api/v2/%s', $endpoint),
            CURLOPT_HTTPHEADER => array(
                "token:$key"
            ),
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
        );
        curl_setopt_array($ch, $options);
        if (!$response = curl_exec($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }
        curl_close($ch);

        return json_decode($response);
    }

    /**
     * @param  Weather $weather
     * @param  \stdClass $data
     * @return Weather
     */
    protected function hydrate(Weather $weather, \stdClass $data)
    {
        print_r($data);
    }

    private function getEndpoint(Location $location)
    {
        return 'data?datasetid=GHCND&startdate=2017-02-01&enddate=2017-02-27&stationid=GHCND:US1PAPH0006';
    }

    private function getKey()
    {
        return $this->config->noaa_key;
    }
}
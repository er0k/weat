<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Config;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;
use Weat\WeatherService;

class Local extends AbstractWeatherService
{
    const TYPE = WeatherService::TYPES['LOCAL'];

    protected Config $config;

    protected function getWeatherDataFromService(Location $location): stdClass
    {
        $jsonData = $this->store->fetchLatest(self::TYPE);

        $data = json_decode($jsonData);

        return $data;
    }

    protected function hydrate(Weather $weather, stdClass $data): Weather
    {
        $weather->current = '';
        $weather->currentTemp = $data->tempf;
        $weather->wind = '';
        $weather->humidity = $data->humidity;
        $weather->visibility = '';
        $weather->pressure = $data->baromabsin;

        return $weather;
    }
}

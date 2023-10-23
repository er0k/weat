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
        $weather->precipitation = "{$data->hourlyrainin}\" hourly ({$data->dailyrainin}\" daily)";
        $weather->wind = "From the {$this->degreesToDirection($data->winddir)} at {$data->windspeedmph} MPH ({$data->windgustmph} MPH gusts)";
        $weather->humidity = $data->humidity;
        $weather->visibility = '';
        $pressure = $this->inchesHgToMillibar($data->baromabsin);
        $weather->pressure = $this->getPressureDifference($pressure, 950, $data->tempf);

        return $weather;
    }
}

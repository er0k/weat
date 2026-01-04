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

    public function getHistory(Location $location): array
    {
        return $this->store->fetchHistory();
    }

    protected function getWeatherDataFromService(Location $location): stdClass
    {
        $jsonData = $this->store->fetchLatest(self::TYPE);

        $data = json_decode($jsonData);

        return $data;
    }

    protected function hydrate(Weather $weather, stdClass $data): Weather
    {
        // print_r($data);

        $weather->location = "Cemetery Hill";
        $weather->currentTemp = $data->tempf;
        $weather->currentIcon = "idk.png";
        $weather->precipitation = "{$data->hourlyrainin}\" hourly ({$data->dailyrainin}\" daily)";
        $weather->precipitationHourly = $data->hourlyrainin;
        $weather->precipitationDaily = $data->dailyrainin;
        $weather->wind = "From the {$this->degreesToDirection($data->winddir)} at {$data->windspeedmph} MPH ({$data->windgustmph} MPH gusts, daily max {$data->maxdailygust} MPH)";
        $weather->uvIndex = $data->uv;
        $weather->solarRadiation = $data->solarradiation;
        $weather->humidity = $data->humidity;
        $weather->dewPoint = $this->getDewPoint($weather->currentTemp, $weather->humidity);
        $weather->visibility = '';

        if (isset($data->baromabsin)) {
            $pressure = $this->inchesHgToMillibar($data->baromabsin);
            $weather->pressure = $this->getPressureDifference($pressure, 950, $data->tempf);
        } else {
            error_log("no pressure data from local station! check barometer");
        }

        $indoor = [];
        foreach($this->getRoomMap() as $id => $name) {
            $tempKey = "temp{$id}f";
            $humidKey = "humidity{$id}";
            $indoor[] = $this->newIndoorRoom($id, $name, $data->$tempKey, $data->$humidKey);
        }
        $weather->zones = $indoor;

        // $weather->alerts = ["here is an alert"];

        $weather->current = $this->getCurrentConditions($weather);

        return $weather;
    }

    private function getCurrentConditions(Weather $weather): string
    {
        $current = '';

        if ($weather->currentTemp < 30) {
            $current .= "fucking cold";
            for ($i = 1; $i < (30 - $weather->currentTemp); $i++) {
                if ($i % 5 == 0) {
                    $current .= "!";
                }
            }
        } elseif ($weather->currentTemp < 35) {
            $current .= "cold";
        } elseif ($weather->currentTemp < 40) {
            $current .= "kinda cold";
        }

        if ($weather->precipitationHourly > 0) {
            $current =  implode(' & ', [$current, "wet"]);
        }

        if (!empty($current)) {
            return $current;
        }

        return "it's fine";
    }

    private function newIndoorRoom($id, $name, $tempF, $humidity)
    {
        $room = new stdClass();
        $room->id = $id;
        $room->name = $name;
        $room->temp = floatval($tempF);
        $room->humidity = intval($humidity);

        return $room;
    }

    private function getRoomMap()
    {
        return [
            'in' => 'living room',
            1 => 'bedroom',
            2 => 'garage',
            3 => 'basement',
            5 => 'office',
            6 => 'baby',
        ];
    }
}

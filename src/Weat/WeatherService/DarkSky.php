<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class DarkSky extends AbstractWeatherService
{
    protected function getWeatherDataFromService(Location $location)
    {
        throw new Exception("Not implemented");
    }

    protected function hydrate(Weather $weather, stdClass $data)
    {
        throw new Exception("Not implemented");
    }
}

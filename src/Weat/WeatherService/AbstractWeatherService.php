<?php

namespace Weat\WeatherService;

use Weat\Config;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;

abstract class AbstractWeatherService
{
    const CACHE_DIR = 'cache';
    const CACHE_TTL = 3600; // in seconds

    /** @var Config */
    protected $config;

    /** @var Location */
    protected $location;

    /** @var string */
    protected $cacheFile;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param  Location $location
     * @return Weat\Weather
     */
    abstract public function getWeather();

    abstract protected function getWeatherDataFromApi();

    public function setLocation(Location $location)
    {
        $this->location = $location;
    }

    protected function getWeatherData()
    {
        $filename = $this->getFilename();

        $this->cacheFile = $filename;

        if (!file_exists($filename) || time() - filemtime($filename) > self::CACHE_TTL) {
            $data = $this->getWeatherDataFromApi();
        } else {
            $data = $this->getWeatherDataFromCache();
        }

        return $data;
    }

    protected function getFilename()
    {
        if (!$this->location) {
            throw new Exception("cannot get file name without location");
        }

        $fullpath = self::CACHE_DIR;

        if (!is_writable($fullpath)) {
            throw new Exception("cache directory is not writable");
        }

        // use static::class here when I upgrade php :p
        $serviceName = md5(get_called_class());

        $fullpath .= '/' . $serviceName;

        if (!file_exists($fullpath)) {
            if (!mkdir($fullpath, 0775)) {
                throw new Exception("could not make cache directory");
            }
        }

        $filename = md5(json_encode($this->location));

        $fullpath .= '/' . $filename;

        return $fullpath;
    }

    /**
     * @return string
     */
    protected function getWeatherDataFromCache()
    {
        if (!$this->cacheFile) {
            throw new Exception("could not get wunderground cache data");
        }

        echo "get data from wunderground cache...\n";

        return json_decode(file_get_contents($this->cacheFile));
    }

    protected function getPressureDifference($currentPressure)
    {
        // millibars at sea level
        $standardPressure = 1013.25;

        return $currentPressure - $standardPressure;
    }

}
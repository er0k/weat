<?php

namespace Weat\WeatherService;

use Weat\Config;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;

abstract class AbstractWeatherService
{
    const CACHE_TTL = 3600; // in seconds

    /** @var Config */
    protected $config;

    /** @var string */
    protected $cacheFile;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param  Location $location
     * @return Weather
     */
    public function getWeather(Location $location)
    {
        if (!$location) {
            throw new Exception("cannot get weather without location");
        }

        $weather = new Weather();

        $data = $this->getWeatherData($location);

        return $this->hydrate($weather, $data);
    }

    /**
     * @param Location $location
     * @return \stdClass
     */
    abstract protected function getWeatherDataFromApi(Location $location);

    /**
     * @param  Weather $weather
     * @param  \stdClass $data
     * @return Weather
     */
    abstract protected function hydrate(Weather $weather, \stdClass $data);


    /**
     * @param Location $location
     * @return \stdClass
     */
    private function getWeatherData(Location $location)
    {
        $cache = $this->getCacheFilename($location);

        if (!file_exists($cache) || time() - filemtime($cache) > self::CACHE_TTL) {
            $this->config->debug('getting weather data from API...');
            $data = $this->getWeatherDataFromApi($location);
        } else {
            $this->config->debug('getting weather data from cache...');
            $data = $this->getWeatherDataFromCache();
        }

        return $data;
    }

    /**
     * @param Location $location
     * @return string
     */
    protected function getCacheFilename(Location $location = null)
    {
        if ($this->cacheFile) {
            return $this->cacheFile;
        }

        if (!$location) {
            throw new Exception("cannot get file name without location");
        }

        $fullpath = $this->getTempDir();

        $this->checkPathPermissions($fullpath);

        // use static::class here when I upgrade php :p
        $serviceName = md5(get_called_class());

        $fullpath .= '/' . $serviceName;

        $this->checkPathPermissions($fullpath);

        $filename = md5(json_encode($location));

        $fullpath .= '/' . $filename;

        $this->cacheFile = $fullpath;

        $this->config->debug("cache file: $fullpath");

        return $this->cacheFile;
    }

    /**
     * @return string
     */
    private function getWeatherDataFromCache()
    {
        $serviceName = get_called_class();

        $cache = $this->getCacheFilename();

        if (!$cache) {
            throw new Exception("could not get {$serviceName} cache data");
        }

        return json_decode(file_get_contents($cache));
    }

    /**
     * @param  int $currentPressure
     * @return int
     */
    protected function getPressureDifference($currentPressure)
    {
        // millibars at sea level
        $standardPressure = 1013.25;

        return $currentPressure - $standardPressure;
    }

    private function getTempdir()
    {
        $tmpDir = sys_get_temp_dir() . '/weat';
        $this->config->debug("temp dir: $tmpDir");

        return $tmpDir;
    }

    /**
     * @param  string $path
     */
    private function checkPathPermissions($path)
    {
        if (!file_exists($path)) {
            if (!mkdir($path, 0775)) {
                throw new Exception("could not make cache {$path}");
            }
        }

        if (!is_writable($path)) {
            throw new Exception("{$path} is not writable");
        }
    }

}

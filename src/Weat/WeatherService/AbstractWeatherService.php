<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Config;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;

abstract class AbstractWeatherService
{
    const CACHE_TTL = 600; // in seconds

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
     * @param  int $degrees
     * @return int
     */
    protected function celsiusToFahrenheit($degrees)
    {
        return $degrees * (9 / 5) + 32;
    }

    /**
     * @param  int $meters
     * @return int
     */
    protected function metersToInches($meters)
    {
        return $meters * 39.3701;
    }

    /**
     * @param  int $meters
     * @return int
     */
    protected function metersToFeet($meters)
    {
        return $meters * 3.28084;
    }

    /**
     * @param  int $meters
     * @return int
     */
    protected function metersToMiles($meters)
    {
        return $meters * 0.00062137;
    }

    /**
     * @link http://snowfence.umn.edu/Components/winddirectionanddegreeswithouttable3.htm
     * @param  int $degrees
     * @return string
     */
    protected function degressToDirection($degrees)
    {
        $val = ($degrees / 22.5) + .5;
        $directions = ['N','NNE','NE','ENE','E','ESE', 'SE', 'SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];

        return $directions[($val % 16)];

    }

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
            $this->saveDataToCache($data, $cache);
        } else {
            $this->config->debug('getting weather data from cache...');
            $data = $this->getWeatherDataFromCache($cache);
        }

        return $data;
    }

    /**
     * @param  stdClass $data
     * @param  string $cache
     */
    private function saveDataToCache(stdClass $data, $cache)
    {
        if (!file_put_contents($cache, json_encode($data))) {
            throw new Exception('could not save cache file');
        }
    }

    /**
     * @param Location $location
     * @return string
     */
    private function getCacheFilename(Location $location)
    {
        if (!$location) {
            throw new Exception("cannot get file name without location");
        }

        $fullpath = $this->getTempDir();

        $this->checkPathPermissions($fullpath);

        $serviceName = md5(static::class);

        $fullpath .= '/' . $serviceName;

        $this->checkPathPermissions($fullpath);

        // remove IP from location before hashing
        // so IPs in the same location can share cache
        $location->ip = '';

        $filename = md5(json_encode($location));

        $fullpath .= '/' . $filename;

        $this->cacheFile = $fullpath;

        $this->config->debug("cache file: $fullpath");

        return $this->cacheFile;
    }

    /**
     * @param $string
     * @return string
     */
    private function getWeatherDataFromCache($cache)
    {
        $serviceName = get_called_class();

        if (empty($cache)) {
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

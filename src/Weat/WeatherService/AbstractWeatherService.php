<?php

namespace Weat\WeatherService;

use \stdClass;
use Weat\Config;
use Weat\Exception;
use Weat\Location;
use Weat\Weather;
use Weat\WeatherStorage;

abstract class AbstractWeatherService
{
    const CACHE_TTL_SECONDS = 600;

    protected const TYPE = -1;

    protected Config $config;

    protected WeatherStorage $store;

    protected string $cacheFile;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->store = new WeatherStorage($config);
    }

    public function getWeather(Location $location): Weather
    {
        if (!$location) {
            throw new Exception("cannot get weather without location");
        }

        $weather = new Weather();
        $weather->service = $this->getServiceType();

        $data = $this->getWeatherData($location);

        return $this->hydrate($weather, $data);
    }

    abstract public function getHistory(Location $location): array;

    abstract protected function getWeatherDataFromService(Location $location): stdClass;

    /**
     * @param  Weather $weather
     * @param  \stdClass $data
     * @return Weather
     */
    abstract protected function hydrate(Weather $weather, \stdClass $data): Weather;

    /**
     * @throws Exception
     */
    protected function request(string $url): stdClass
    {
        error_log("requesting $url");
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
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
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        error_log("responseCode: $responseCode");
        curl_close($ch);

        $jsonData = json_decode($responseBody);

        if (is_null($jsonData)) {
            error_log("responseHeaders: $responseHeaders");
            error_log("responseBody: $responseBody");
            throw new Exception("Could not decode data!");
        }

        return $jsonData;
    }

    /**
     * @param  int|string $tempF
     */
    protected function celsiusToFahrenheit($tempF): int
    {
        return $tempF * (9 / 5) + 32;
    }

    protected function fahrenheitToCelsius(float $tempC): float
    {
        return ($tempC - 32) * (5 / 9);
    }

    protected function celsiusToKelvin(float $tempC): float
    {
        return $tempC + 273.15;
    }

    protected function metersToInches(float $meters): float
    {
        return $meters * 39.3701;
    }

    protected function millimetersToInches(float $mm): float
    {
        return $this->metersToInches($mm / 1000);
    }

    protected function metersToFeet(float $meters): float
    {
        return $meters * 3.28084;
    }

    protected function feetToMeters(float $feet): float
    {
        return $feet / 3.28084;
    }

    protected function metersToMiles(float $meters): float
    {
        return $meters * 0.00062137;
    }

    protected function metersPerSecondToMilesPerHour(float $mps): float
    {
        return $mps * 2.2369362942913;
    }

    /**
     * @link http://snowfence.umn.edu/Components/winddirectionanddegreeswithouttable3.htm
     */
    protected function degreesToDirection(int $degrees): string
    {
        $val = ($degrees / 22.5) + .5;
        $directions = ['N','NNE','NE','ENE','E','ESE', 'SE', 'SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];

        return $directions[($val % 16)];
    }

    protected function pascalToMillibar(float $pascal): float
    {
        return $pascal * 0.01;
    }

    protected function inchesHgToMillibar(float $inches): float
    {
        return $inches * 33.8637526;
    }

    /**
     * @link https://www.omnicalculator.com/physics/air-pressure-at-altitude
     */
    protected function getPressureAtAltitude(float $feet, float $tempF): float
    {
        // pressure decreases as altitude rises
        // cold air is more dense and so has higher pressure
        // warm is is less dense and so has lower pressure
        $h = $this->feetToMeters($feet); // height in meters
        $P0 = 101325; // pressure at sea level in pascals
        $g = 9.80665; // acceleration of gravity in m/s^2
        $M = 0.0289644; // molar mass of air in kg/mol
        $R = 8.31432; // universal gas constant
        $T = $this->celsiusToKelvin($this->fahrenheitToCelsius($tempF)); // temperature in kelvin
        $P = $P0 * exp( (-$g * $M * $h) / ($R * $T) );

        return $this->pascalToMillibar($P);
    }

    protected function getPressureDifference(int $currentPressure, float $feet = 0, float $tempF = 72): float
    {
        $standardPressure = $this->getPressureAtAltitude($feet, $tempF);

        if ($currentPressure == 0) {
            return 0;
        }

        return round($currentPressure - $standardPressure, 2);
    }

    /**
     * @link https://iridl.ldeo.columbia.edu/dochelp/QA/Basic/dewpoint.html
     */
    protected function getDewPoint(float $tempF, float $humidity): float
    {
        $T = $this->fahrenheitToCelsius($tempF);
        $RH = $humidity;
        $Td = $T - (( 100 - $RH ) / 5);

        return $this->celsiusToFahrenheit($Td);
    }


    private function getServiceType(): int
    {
        return static::TYPE;
    }

    private function getWeatherData(Location $location): stdClass
    {
        $cache = $this->getCacheFilename($location);

        $skipCache = $_GET['nocache'] ?? false;

        if (!file_exists($cache) || time() - filemtime($cache) > self::CACHE_TTL_SECONDS || $skipCache) {
            error_log("fetching weather from service");
            $data = $this->getWeatherDataFromService($location);
            $data->isCached = false;
            $this->saveDataToCache($data, $cache);
        } else {
            error_log("fetching weather from cache");
            $data = $this->getWeatherDataFromCache($cache);
            $data->isCached = true;
        }

        return $data;
    }

    private function saveDataToCache(stdClass $data, string $cache): void
    {
        if (!file_put_contents($cache, json_encode($data))) {
            throw new Exception('could not save cache file');
        }
    }

    private function getCacheFilename(Location $location): string
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
        $ip = $location->ip;
        $location->ip = '';

        $filename = md5(json_encode($location));

        $location->ip = $ip;

        $fullpath .= '/' . $filename;

        $this->cacheFile = $fullpath;

        error_log("cachefile: " . $this->cacheFile);

        return $this->cacheFile;
    }

    private function getWeatherDataFromCache(string $cache): stdClass
    {
        $serviceName = get_called_class();

        if (empty($cache)) {
            throw new Exception("could not get {$serviceName} cache data");
        }

        return json_decode(file_get_contents($cache));
    }

    private function getTempdir(): string
    {
        $tmpDir = sys_get_temp_dir() . '/weat';

        return $tmpDir;
    }

    private function checkPathPermissions(string $path): void
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

<?php

namespace Weat;

use GeoIp2\Database\Reader as GeoIP;
use Weat\Config;
use Weat\Exception;
use Weat\Location;

class Locator
{
    private Config $config;
    private ?GeoIP $geoip = null;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->geoip = $this->getGeoIP($this->config->city_db);
    }

    public function getLocation(): Location
    {
        return $this->getLocationFromIP();
    }

    private function getGeoIP(string $dbPath): GeoIP
    {
        if ($this->geoip) {
            return $this->geoip;
        }

        return new GeoIP($this->getGeoIPDatabase($dbPath));
    }

    private function getGeoIPDatabase(string $dbPath): string
    {
        if (!file_exists($dbPath)) {
            throw new Exception("no geoip db at {$dbPath}");
        }

        if (!is_readable($dbPath)) {
            throw new Exception("can't read geoip db: {$dbPath}");
        }

        return $dbPath;
    }

    /**
     * @throws Exception
     */
    private function getLocationFromIP(): Location
    {
        $ip = $_GET['ip'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ips = explode(',', $ip);
        $ip = reset($ips);

        error_log("ip: $ip");

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) ) {
            // if it's a local IP, no need to try and geolocate
            return $this->getDefaultLocation();
        }

        try {
            $record = $this->geoip->city($ip);
        } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
            throw new Exception("Could not geolocate your IP", 1, $e);
        } catch (\MaxMind\Db\Reader\InvalidDatabaseException $e) {
            throw new Exception("Could not read geolocation database", 2, $e);
        }

        $location = new Location();
        $location->ip = $ip;
        $location->country = $record->country->name;
        $location->city = $record->city->name;
        $location->state = $record->mostSpecificSubdivision->name;
        $location->zip = $record->postal->code;
        $location->lat = $record->location->latitude;
        $location->lon = $record->location->longitude;
        $location->timezone = $record->location->timeZone;

        return $location;
    }

    private function getDefaultLocation(): Location
    {
        $defaultLocation = $this->config->default_location;

        $location = new Location();
        $location->ip = '127.0.0.1';
        $location->country = $defaultLocation['country'];
        $location->city = $defaultLocation['city'];
        $location->state = $defaultLocation['state'];
        $location->zip = $defaultLocation['zip'];
        $location->lat = $defaultLocation['lat'];
        $location->lon = $defaultLocation['lon'];
        $location->timezone = $defaultLocation['timezone'];

        return $location;
    }
}

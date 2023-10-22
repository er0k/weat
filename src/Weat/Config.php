<?php

namespace Weat;

class Config
{
    const CONFIG_FILE = 'config/config.php';

    public string $open_weather_map_key;
    public string $weat_db;
    public string $city_db;
    public string $station_type;
    public string $station_id;
    public array $default_location;

    public function __construct()
    {
        if (!$config = include self::CONFIG_FILE) {
            throw new Exception('No config file');
        }

        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }
}

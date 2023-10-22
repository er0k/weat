<?php

namespace Weat;

use Weat\Config;
use Weat\WeatherService;
use Weat\WeatherStorage;

class Receiver
{
    private Config $config;
    private WeatherStorage $store;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->store = new WeatherStorage($config);
    }

    public function save(): Void
    {
        $data = $_REQUEST;

        if (!$this->isMyStation($data)) {
            $this->forbid();
        }

        $this->store->save(WeatherService::TYPES['LOCAL'], $data);
    }

    private function isMyStation(array $data): bool
    {
        if (!isset($data['stationtype'])) {
            error_log("no station type given");
            return false;
        }

        if (!isset($data['PASSKEY'])) {
            error_log("no station ID given");
            return false;
        }

        if ($data['stationtype'] != $this->config->station_type) {
            error_log("station type {$$data['stationtype']} did not match expected: {$this->config->station_type}");
            return false;
        }

        if ($data['PASSKEY'] != $this->config->station_id) {
            error_log("station ID {$$data['PASSKEY']} did not match expected: {$this->config->station_id}");
            return false;
        }

        return true;
    }

    private function forbid(): Void
    {
        http_response_code(403);
        die();
    }
}

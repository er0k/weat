<?php

namespace Weat;

use Weat\Config;
use SleekDB\Store;

class Receiver
{
    private Config $config;

    private Store $store;

    public function __construct(Config $config, Store $store)
    {
        $this->config = $config;
        $this->store = $store;
    }

    public function save(): Void
    {
        /**
            stationtype
            PASSKEY
            dateutc
            tempf
            humidity
            windspeedmph
            windgustmph
            maxdailygust
            winddir
            winddir_avg10m
            uv
            solarradiation
            hourlyrainin
            dailyrainin
            weeklyrainin
            monthlyrainin
            yearlyrainin
            battout
            tempinf
            humidityin
            baromrelin
            baromabsin
            battin
            temp1f
            humidity1
            temp2f
            humidity2
            temp3f
            humidity3
            batt1
            batt2
            batt3
            batt_co2
        **/

        $data = $_REQUEST;

        if (!$this->isMyStation($data)) {
            $this->forbid();
        }

        $this->store->insert($data);

        return;
    }

    private function isMyStation(array $data): bool {
        if (!isset($data['stationtype'])) {
            error_log("no station type given");
            return false;
        }

        if (!isset ($data['PASSKEY'])) {
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

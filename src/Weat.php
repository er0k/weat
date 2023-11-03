<?php

use Weat\Config;
use Weat\Locator;
use Weat\Receiver;
use Weat\LunarTracker;
use Weat\SolarTracker;
use Weat\WeatherService;

class Weat
{
    public function run(): string
    {
        $config = new Config();

        if ($this->wantsNothing()) {
            (new Receiver($config))->save();
            return '';
        }

        if ($this->wantsList()) {
            return $this->sendJson($this->getActiveServices($config));
        }

        $location = (new Locator($config))->getLocation();

        if ($this->wantsMoon()) {
            return $this->sendJson((new LunarTracker())->getMoons($location));
        }

        if ($this->wantsLocation()) {
            return $this->sendJson($location);
        }

        if ($this->wantsSun()) {
            return $this->sendJson((new SolarTracker())->getSuns($location));
        }

        $service = $this->getService();

        if ($this->wantsWeather()) {
            return $this->sendJson((new WeatherService($config, $service))->getWeather($location));
        }
    }

    private function wantsList(): bool
    {
        return isset($_GET['l']);
    }

    private function wantsMoon(): bool
    {
        return isset($_GET['m']);
    }

    private function wantsSun(): bool
    {
        return isset($_GET['s']);
    }

    private function wantsLocation(): bool
    {
        return isset($_GET['x']);
    }

    private function wantsWeather(): bool
    {
        return isset($_GET['w']);
    }

    private function wantsNothing(): bool
    {
        if (
            $this->wantsList()
            || $this->wantsMoon()
            || $this->wantsSun()
            || $this->wantsLocation()
            || $this->wantsWeather()
        ) {
            return false;
        }

        return true;
    }

    private function getActiveServices(Config $config): array
    {
        $serviceList = WeatherService::ACTIVE_TYPES;

        // don't show local weather station at the public URL
        if ($_SERVER['HTTP_HOST'] == $config->public_url) {
            unset($serviceList[WeatherService::TYPES['LOCAL']]);
        }

        return array_values($serviceList);
    }

    private function getService(): int
    {
        if (!in_array($_GET['w'], WeatherService::TYPES)) {
            $this->badRequest();
        }

        return $_GET['w'];
    }

    private function badRequest(): Void
    {
        http_response_code(400);
        die();
    }

    private function sendJson($out): string
    {
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($out);
    }

}

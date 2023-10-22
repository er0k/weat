<?php

use Weat\Config;
use Weat\Location;
use Weat\Locator;
use Weat\Exception;
use Weat\Receiver;
use Weat\SolarTracker;
use Weat\Weather;
use Weat\WeatherService;

class Weat
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run(): ?string
    {
        if (!$this->isServiceRequested()) {
            (new Receiver($this->config))->save();
            return null;
        }

        $location = (new Locator($this->config))->getLocation();

        $weather = (new WeatherService(
            $this->config,
            $this->getService()
        ))->getWeather($location);

        $sun = (new SolarTracker())->getSun($location, $weather);

        $output = [
            'location' => $location,
            'weather' => $weather,
            'sun' => $sun,
        ];

        return $this->sendJson($output);
    }

    private function isServiceRequested(): bool
    {
        if (isset($_GET['s'])) {
            return true;
        }

        return false;
    }

    private function getService(): int
    {
        if (!in_array($_GET['s'], WeatherService::TYPES)) {
            $this->badRequest();
        }

        return $_GET['s'];
    }

    private function badRequest(): Void
    {
        http_response_code(400);
        die();
    }

    private function sendJson(array $out): string
    {
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($out);
    }

}

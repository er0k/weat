<?php

use Weat\Config;
use Weat\Locator;
use Weat\Receiver;
use Weat\SolarTracker;
use Weat\WeatherService;

class Weat
{
    public function run(): string
    {
        $config = new Config();

        if (!$this->isServiceRequested()) {
            (new Receiver($config))->save();
            return '';
        }

        $service = $this->getService();

        $location = (new Locator($config))->getLocation();
        $weather = (new WeatherService($config, $service))->getWeather($location);
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

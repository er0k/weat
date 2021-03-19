<?php

namespace Weat;

class Weather
{
    public bool $isCached;

    public string $location;

    public string $timeFriendly;

    public string $timeRfc;

    // Human readable current conditions
    public string $current;

    // Degrees in fahrenheit
    public float $currentTemp;

    // URL to image icon
    public string $currentIcon;

    /** @var array */
    public $alerts;

    // Percentage of humidity
    public int $humidity;

    public string $wind;

    public float $pressure;

    public string $visibility;

    public string $precipitation;

    public string $moon;

    public string $sunrise;

    public string $sunset;

    public string $average;

    public string $record;

    public string $forecast;

    public string $tides;

    public string $satellite;

    public string $timezone;

    public string $lat;

    public string $lon;

    public string $epoch;

    public string $elevation;

    public ?string $dewpoint;

    public string $clouds;
}

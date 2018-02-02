<?php

namespace Weat;

class Weather
{
    /** @var string */
    public $location;

    /** @var string */
    public $timeFriendly;

    /** @var string */
    public $timeRfc;

    /**
     * Human readable current conditions
     * @var string
     */
    public $current;

    /**
     * Degrees in fahrenheit
     * @var float
     */
    public $currentTemp;

    /**
     * URL to image icon
     * @var string
     */
    public $currentIcon;

    /** @var array */
    public $alerts;

    /**
     * Percentage of humidity
     * @var int
     */
    public $humidity;

    /** @var string */
    public $wind;

    /** @var string */
    public $pressure;

    /** @var string */
    public $visibility;

    /** @var string */
    public $precipitation;

    /** @var string */
    public $moon;

    /** @var string */
    public $sunrise;

    /** @var string */
    public $sunset;

    /** @var string */
    public $average;

    /** @var string */
    public $record;

    /** @var string */
    public $forecast;

    /** @var string */
    public $tides;

    /** @var string */
    public $satellite;

    /** @var string */
    public $timezone;

    /** @var string */
    public $lat;

    /** @var string */
    public $lon;

    /** @var string */
    public $epoch;

    /** @var string */
    public $elevation;

    /** @var string */
    public $dewpoint;

    /** @var string */
    public $clouds;
}

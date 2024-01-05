<?php

namespace Weat;

use DateTime;
use DateTimeZone;
use Solaris\MoonPhase;
use Weat\Location;
use Weat\Moon;

class LunarTracker
{
    public $foo;

    public function getMoon(Location $location, string $time): Moon
    {
        $moon = new Moon();

        $dateTimeZone= new DateTimeZone($location->timezone);
        $dateTime = new DateTime($time);

        $mp = new MoonPhase($dateTime);

        $moon->phase = $mp->getPhaseName();
        $moon->illumination = round($mp->getIllumination(), 4) * 100;
        $moon->age = round($mp->getAge(), 2);
        $moon->fullCurrent = $this->formatTime($mp->getPhaseFullMoon(), $dateTimeZone);
        $moon->fullNext = $this->formatTime($mp->getPhaseNextFullMoon(), $dateTimeZone);
        $moon->newCurrent = $this->formatTime($mp->getPhaseNewMoon(), $dateTimeZone);
        $moon->newNext = $this->formatTime($mp->getPhaseNextNewMoon(), $dateTimeZone);


        return $moon;
    }

    public function getMoons(Location $location): array
    {
        $moons = [];
        foreach (['yesterday', 'today', 'tomorrow'] as $time) {
            $moons[$time] = $this->getMoon($location, $time);
        }

        return $moons;
    }

    private function formatTime(int $time, DateTimeZone $dt): string
    {
        $t = new DateTime('@'. $time);
        return $t->setTimeZone($dt)->format(DateTime::ATOM);
    }
}

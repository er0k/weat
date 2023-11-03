<?php

namespace Weat;

use DateTime;
use DateTimeZone;
use Weat\Location;
use Weat\Sun;

class SolarTracker
{
    public function getSun(Location $location, string $time): Sun
    {
        $sun = new Sun();

        if (!$location->timezone || !$location->lat || !$location->lon) {
            return $sun;
        }

        $dateTimeZone= new DateTimeZone($location->timezone);
        $dateTime = new DateTime($time, $dateTimeZone);

        $sunInfo = date_sun_info($dateTime->getTimeStamp(), $location->lat, $location->lon);

        foreach ($this->sunKeysMap() as $phpKey => $weatKey) {
            $t = new DateTime('@' . $sunInfo[$phpKey]);
            $sun->{$weatKey} = $t->setTimeZone($dateTimeZone)->format(DateTime::ATOM);
        }
        return $sun;
    }

    public function getSuns(Location $location): array
    {
        $suns = [];
        foreach (['yesterday', 'today', 'tomorrow'] as $time) {
            $suns[$time] = $this->getSun($location, $time);
        }

        return $suns;
    }

    private function sunKeysMap(): array
    {
        return [
            'astronomical_twilight_begin' => 'astronomicalDawn',
            'nautical_twilight_begin' => 'nauticalDawn',
            'civil_twilight_begin' => 'civilDawn',
            'sunrise' => 'rise',
            'transit' => 'zenith',
            'sunset' => 'set',
            'civil_twilight_end' => 'civilDusk',
            'nautical_twilight_end' => 'nauticalDusk',
            'astronomical_twilight_end' => 'astronomicalDusk',
        ];
    }
}

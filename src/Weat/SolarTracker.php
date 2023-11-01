<?php

namespace Weat;

use DateTime;
use DateTimeZone;
use Weat\Location;
use Weat\Sun;

class SolarTracker
{
    public function getSun(Location $location): Sun
    {
        $sun = new Sun();

        if (!$location->timezone || !$location->lat || !$location->lon) {
            return $sun;
        }

        $dateTimeZone= new DateTimeZone($location->timezone);

        $sunInfo = date_sun_info(time(), $location->lat, $location->lon);

        $sun->astronomical_dawn = $this->cleanTime($sunInfo['astronomical_twilight_begin'], $dateTimeZone);
        $sun->nautical_dawn = $this->cleanTime($sunInfo['nautical_twilight_begin'], $dateTimeZone);
        $sun->civil_dawn = $this->cleanTime($sunInfo['civil_twilight_begin'], $dateTimeZone);
        $sun->rise = $this->cleanTime($sunInfo['sunrise'], $dateTimeZone);
        $sun->zenith = $this->cleanTime($sunInfo['transit'], $dateTimeZone);
        $sun->set = $this->cleanTime($sunInfo['sunset'], $dateTimeZone);
        $sun->civil_dusk = $this->cleanTime($sunInfo['civil_twilight_end'], $dateTimeZone);
        $sun->nautical_dusk = $this->cleanTime($sunInfo['nautical_twilight_end'], $dateTimeZone);
        $sun->astronomical_dusk = $this->cleanTime($sunInfo['astronomical_twilight_end'], $dateTimeZone);

        return $sun;
    }

    private function cleanTime(int $epoch, DateTimeZone $tz): string
    {
        return (new DateTime())
            ->setTimeZone($tz)
            ->setTimestamp($epoch)
            ->format(DateTime::ATOM);
    }
}

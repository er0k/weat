<?php

namespace Weat;

/**
 * sunrise or sunset is defined to occur when the geometric zenith distance of
 * the center of the Sun is 90.8333 degrees. That is, the center of the Sun is
 * geometrically 50 arcminutes below a horizontal plane. For an observer at sea
 * level with a level, unobstructed horizon, under average atmospheric
 * conditions, the upper limb of the Sun will then appear to be tangent to the
 * horizon. The 50-arcminute geometric depression of the Sun's center used for
 * the computations is obtained by adding the average apparent radius of the Sun
 * (16 arcminutes) to the average amount of atmospheric refraction at the
 * horizon (34 arcminutes).
 *
 * civil twilight begins before sunrise and ends after sunset when the geometric
 * zenith distance of the center of the Sun is 96 degrees - 6 degrees below a
 * horizontal plane. The corresponding solar zenith distances for nautical and
 * astronomical twilight are 102 and 108 degrees, respectively. That is, at the
 * dark limit of nautical twilight, the center of the Sun is geometrically 12
 * degrees below a horizontal plane; and at the dark limit of astronomical
 * twilight, the center of the Sun is geometrically 18 degrees below a
 * horizontal plane.
 *
 * @link https://aa.usno.navy.mil/faq/RST_defs
 */
class Sun
{
    public string $astronomical_dawn;
    public string $nautical_dawn;
    public string $civil_dawn;
    public string $rise;
    public string $zenith;
    public string $set;
    public string $civil_dusk;
    public string $nautical_dusk;
    public string $astronomical_dusk;
}

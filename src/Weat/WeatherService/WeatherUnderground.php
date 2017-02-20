<?php

namespace Weat\WeatherService;

use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class WeatherUnderground extends AbstractWeatherService
{

    /**
     * @param Location $location
     * @return \stdClass
     */
    protected function getWeatherDataFromApi(Location $location)
    {
        $key = $this->config->wunderground_key;

        $features = array(
            'alerts',
            'almanac',
            'astronomy',
            'conditions',
            'forecast',
            'geolookup',
            'satellite',
            'tide',
        );

        $featuresList = implode('/', $features);

        $query = $this->getQuery($location);

        $url = "https://api.wunderground.com/api/$key/$featuresList/q/$query";

        $jsonData = file_get_contents($url);
        if ($jsonData === false) {
            throw new Exception("couldn't get the wundergroud JSON data. might have exceeded API limits");
        }

        // all weather underground's links are http, but https seems to work just fine
        // I can't find any way to prefer https, so just replace them here
        $jsonData = str_replace('http://', 'https://', $jsonData);

        $data = json_decode($jsonData);

        if (isset($data->response->error)) {
            throw new Exception("wunderground API error: " . $data->response->error->description);
        }

        if (!file_put_contents($this->cacheFile, json_encode($data))) {
            throw new Exception("Could not write wunderground cache file");
        }

        return $data;
    }

    /**
     * @param  Weather $weather
     * @param  \stdClass $data
     * @return Weather
     */
    protected function hydrate(Weather $weather, \stdClass $data)
    {
        $weather->location = $data->current_observation->display_location->full;
        $weather->time = $data->current_observation->observation_time;
        $weather->current = $data->current_observation->weather;
        $weather->currentTemp = $data->current_observation->temp_f . 'F (feels like ' . $data->current_observation->feelslike_f . ')';
        $weather->currentIcon = $data->current_observation->icon_url;
        $weather->alerts = array();
        foreach ($data->alerts as $alert) {
            $weather->alerts[] = $alert->message;
        }
        $weather->humidty = $data->current_observation->relative_humidity;
        $weather->wind = $data->current_observation->wind_string;
        $weather->pressure = $this->getPressureDifference($data->current_observation->pressure_mb)  . 'mb ' . $data->current_observation->pressure_trend;
        $weather->visibility = $data->current_observation->visibility_mi . ' miles';
        $weather->precipitation = $data->current_observation->precip_today_in . ' inches';
        $weather->moon = $data->moon_phase->phaseofMoon . ' (' . $data->moon_phase->percentIlluminated . '% illuminated)';
        $weather->average = '&uarr;' . $data->almanac->temp_high->normal->F . 'F &darr;' . $data->almanac->temp_low->normal->F . 'F';
        $weather->record = '&uarr;' . $data->almanac->temp_high->record->F . 'F (' . $data->almanac->temp_high->recordyear . ') &darr;' . $data->almanac->temp_low->record->F . 'F (' . $data->almanac->temp_low->recordyear . ')';
        $weather->forecast = array();
        foreach ($data->forecast->txt_forecast->forecastday as $day) {
            $weather->forecast[] = array(
                'day' => $day->title,
                'icon' => $day->icon_url,
                'summary' => $day->fcttext,
            );
        }
        $weather->tides = array();
        foreach ($data->tide->tideSummary as $tide) {
            $weather->tides[] = array(
                'date' => $tide->date->pretty,
                'type' => $tide->data->type,
                'height' => $tide->data->height,
            );
        }
        $weather->satellite = $data->satellite->image_url;

        $sun = $this->getRelativeSunTimes($data);
        $weather->sunrise = $sun['rise'];
        $weather->sunset = $sun['set'];

        return $weather;
    }

    /**
     * @param Location $location
     * @return string
     * @link https://www.wunderground.com/weather/api/d/docs?d=data/index
     */
    private function getQuery(Location $location)
    {
        if (!empty($location->zip)) {
            return $location->zip . '.json';
        }

        if (!empty($location->lat) && !empty($location->lon)) {
            return sprintf("%s,%s.json", $location->lat, $location->lon);
        }

        if (!empty($location->ip)) {
            if (filter_var($location->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) ) {
                return "autoip.json?geo_ip={$ip}";
            }
        }

        return 'autoip.json';
    }

    private function getRelativeSunTimes($data)
    {
        $wgSunriseTime = $data->moon_phase->sunrise->hour . ':' . $data->moon_phase->sunrise->minute;
        $wgSunsetTime = $data->moon_phase->sunset->hour . ':' . $data->moon_phase->sunset->minute;

        $tz = $data->current_observation->local_tz_long;
        $lat = $data->current_observation->display_location->latitude;
        $lon = $data->current_observation->display_location->longitude;
        $epoch = $data->current_observation->observation_epoch;
        $timeString = $data->current_observation->observation_time_rfc822;

        $dtz = new \DateTimeZone($tz);
        $dt = new \DateTime($timeString, $dtz);
        $offsetInSeconds = $dtz->getOffset($dt);
        $offset = $offsetInSeconds / 60 / 60;
        $zenith = 90 + (50 / 60);

        $sunriseTime = date_sunrise($epoch, SUNFUNCS_RET_STRING, $lat, $lon, $zenith, $offset);
        $sunsetTime = date_sunset($epoch, SUNFUNCS_RET_STRING, $lat, $lon, $zenith, $offset);

        $now = new \DateTime('now', $dtz);

        $sunriseDatetime = new \DateTime($sunriseTime, $dtz);
        $sunsetDatetime = new \DateTime($sunsetTime, $dtz);

        $riseDiff = $now->diff($sunriseDatetime);
        $setDiff = $now->diff($sunsetDatetime);

        $riseDiffString = $riseDiff->format("%R%h:%I");
        $setDiffString = $setDiff->format("%R%h:%I");

        return array(
            'rise' => "$sunriseTime ($riseDiffString)",
            'set' => "$sunsetTime ($setDiffString)",
        );
    }
}
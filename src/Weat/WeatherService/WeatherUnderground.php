<?php

namespace Weat\WeatherService;

use Weat\Exception;
use Weat\Location;
use Weat\Weather;

class WeatherUnderground extends AbstractWeatherService
{

    /**
     * @param Location $location
     * @throws Exception
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

        $cache = $this->getCacheFilename();

        if (!file_put_contents($cache, json_encode($data))) {
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
        $weather->timeFriendly = $data->current_observation->observation_time;
        $weather->current = $data->current_observation->weather;
        $weather->currentTemp = $data->current_observation->temp_f . 'F (feels like ' . $data->current_observation->feelslike_f . ')';
        $weather->currentIcon = $data->current_observation->icon_url;
        $weather->alerts = array();
        foreach ($data->alerts as $alert) {
            $weather->alerts[] = $alert->message;
        }
        $weather->humidity = $data->current_observation->relative_humidity;
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

        $weather->sunrise = $data->moon_phase->sunrise->hour . ':' . $data->moon_phase->sunrise->minute;
        $weather->sunset = $data->moon_phase->sunset->hour . ':' . $data->moon_phase->sunset->minute;

        $weather->timezone = $data->current_observation->local_tz_long;
        $weather->timeRfc = $data->current_observation->observation_time_rfc822;
        $weather->lat = $data->current_observation->display_location->latitude;
        $weather->lon = $data->current_observation->display_location->longitude;
        $weather->epoch = $data->current_observation->observation_epoch;

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
            return sprintf('%s.json', $location->zip);
        }

        if (!empty($location->lat) && !empty($location->lon)) {
            return sprintf('%s,%s.json', $location->lat, $location->lon);
        }

        if (!empty($location->ip)) {
            if (filter_var($location->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) ) {
                return sprintf('autoip.json?geo_ip=%s', $ip);
            }
        }

        return 'autoip.json';
    }
}

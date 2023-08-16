<?php

namespace App\Service\base;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceHelper
{
    /**
     * This is a PHP function that calculates the distance between two sets of latitude and longitude
     * coordinates, with an optional maximum distance parameter.
     *
     * @param lat1 The latitude of the first location in degrees.
     * @param lon1 longitude of the first location
     * @param lat2 The latitude of the second location in degrees.
     * @param lon2 The longitude of the second location in decimal degrees.
     * @param distanceMax The maximum distance allowed between two points. If the calculated distance
     * between the two points is greater than this value, the function will return null. If the value
     * is not provided, the function will return the calculated distance between the two points.
     *
     * @return ?float either a float value representing the distance between two sets of latitude and
     * longitude coordinates in kilometers, or a boolean value indicating whether the distance is less
     * than or equal to a specified maximum distance.
     */
    public static function distance($lat1, $lon1, $lat2, $lon2, $distanceMax = null): ?float
    {
        $earthRadius = 6371; // rayon moyen de la Terre en kilomètres
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);
        $a = sin($latDiff / 2) * sin($latDiff / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDiff / 2) * sin($lonDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        if ($distanceMax == null) {
            return $distance;
        } else {
            return $distance <= $distanceMax;
        }
    }
}

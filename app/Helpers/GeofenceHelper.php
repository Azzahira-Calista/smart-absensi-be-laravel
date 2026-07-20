<?php

namespace App\Helpers;

class GeofenceHelper
{
    /**
     * Tipe Radius: Menghitung jarak antara 2 titik menggunakan Rumus Haversine
     */
    public static function checkRadius($userLat, $userLng, $centerLat, $centerLng, $radiusMeter)
    {
        $earthRadius = 6371000; // Dalam satuan meter

        $latFrom = deg2rad($userLat);
        $lonFrom = deg2rad($userLng);
        $latTo = deg2rad($centerLat);
        $lonTo = deg2rad($centerLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            
        $distance = $angle * $earthRadius;

        return $distance <= $radiusMeter;
    }

    /**
     * Tipe Mapping: Menggunakan Algoritma Ray-Casting (Point-in-Polygon)
     */
    public static function checkMapping($userLat, $userLng, $polygonVertices)
    {
        $inside = false;
        $numVertices = count($polygonVertices);
        
        // Loop melewati setiap sisi polygon
        for ($i = 0, $j = $numVertices - 1; $i < $numVertices; $j = $i++) {
            $xi = (float) $polygonVertices[$i]['lng'];
            $yi = (float) $polygonVertices[$i]['lat'];
            $xj = (float) $polygonVertices[$j]['lng'];
            $yj = (float) $polygonVertices[$j]['lat'];

            $intersect = (($yi > $userLat) != ($yj > $userLat))
                && ($userLng < ($xj - $xi) * ($userLat - $yi) / ($yj - $yi) + $xi);
                
            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
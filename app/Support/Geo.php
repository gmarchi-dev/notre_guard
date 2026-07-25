<?php

namespace App\Support;

class Geo
{
    /**
     * Distância entre duas coordenadas, em metros (Haversine).
     *
     * Precisão de sobra para o uso aqui: raios de checkpoint são de dezenas de
     * metros, onde o erro do modelo esférico é irrelevante perto do erro do GPS.
     */
    public static function distanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
    ): float {
        $earthRadius = 6_371_000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Kompleks DPR RI (Tipe Mapping)
        // Kumpulan koordinat lat/lng dari JSON dikelompokkan ke dalam satu array polygon
        $dprCoords = [
            ["lat" => "-6.206472", "lng" => "106.802011"],
            ["lat" => "-6.208458", "lng" => "106.799642"],
            ["lat" => "-6.207655", "lng" => "106.798625"],
            ["lat" => "-6.207935", "lng" => "106.798312"],
            ["lat" => "-6.207893", "lng" => "106.798165"],
            ["lat" => "-6.208061", "lng" => "106.797797"],
            ["lat" => "-6.208289", "lng" => "106.797498"],
            ["lat" => "-6.209683", "lng" => "106.797036"],
            ["lat" => "-6.210006", "lng" => "106.796904"],
            ["lat" => "-6.210850", "lng" => "106.796932"],
            ["lat" => "-6.211945", "lng" => "106.797107"],
            ["lat" => "-6.212583", "lng" => "106.797398"],
            ["lat" => "-6.213007", "lng" => "106.797744"],
            ["lat" => "-6.212914", "lng" => "106.797905"],
            ["lat" => "-6.212833", "lng" => "106.797897"],
            ["lat" => "-6.212486", "lng" => "106.797709"],
            ["lat" => "-6.212363", "lng" => "106.798037"],
            ["lat" => "-6.212289", "lng" => "106.798783"],
            ["lat" => "-6.212137", "lng" => "106.799503"],
            ["lat" => "-6.212339", "lng" => "106.799605"],
            ["lat" => "-6.211690", "lng" => "106.802006"],
            ["lat" => "-6.211166", "lng" => "106.802635"],
            ["lat" => "-6.210286", "lng" => "106.803907"],
            ["lat" => "-6.209762", "lng" => "106.803583"],
            ["lat" => "-6.209577", "lng" => "106.803774"],
            ["lat" => "-6.210010", "lng" => "106.804211"],
            ["lat" => "-6.209602", "lng" => "106.804634"],
            ["lat" => "-6.206613", "lng" => "106.802050"],
            ["lat" => "-6.206472", "lng" => "106.802011"]
        ];

        Location::create([
            'id' => 1,
            'name' => 'Kompleks DPR RI',
            'type' => 'Mapping',
            'polygon_coords' => json_encode($dprCoords)
        ]);

        // 2. Data Beberapa Titik Tunggal (Tipe Radius)
        Location::create([
            'id' => 30,
            'name' => 'Terminal 2F (Bandara)',
            'type' => 'Radius',
            'latitude' => -6.124028,
            'longitude' => 106.653721,
            'radius_meter' => 50
        ]);

        Location::create([
            'id' => 31,
            'name' => 'Kantor Pengelola Wisma Kopo',
            'type' => 'Radius',
            'latitude' => -6.667827,
            'longitude' => 106.921000,
            'radius_meter' => 50
        ]);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /**
     * Kolom yang diizinkan untuk diisi secara massal.
     * Bermanfaat jika sewaktu-waktu Anda membuat fitur CRUD Lokasi/Geofence dari web admin.
     */
    protected $fillable = [
        'name',
        'type', // 'Radius' atau 'Mapping'
        'latitude',
        'longitude',
        'radius_meter',
        'polygon_coords', // Menyimpan koordinat area polygon
    ];

    /**
     * Konversi tipe data otomatis (Casting).
     * Memastikan data koordinat polygon otomatis dikonversi menjadi array saat diakses.
     */
    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
        'radius_meter' => 'integer',
        'polygon_coords' => 'array', // Mengubah string JSON di DB langsung menjadi Array di PHP
    ];
}
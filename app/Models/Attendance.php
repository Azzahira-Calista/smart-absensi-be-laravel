<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    // Tentukan nama tabel jika nama tabel Anda di database bukan "attendances"
    // protected $table = 'attendances';

    /**
     * Kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'user_id',
        'location_id',
        'action_type',
        'attendance_type',
        'user_latitude',
        'user_longitude',
        'status',
        'device_id',
        'is_mock_location',
        'note',
        'attachment_path',
    ];

    /**
     * Konversi tipe data otomatis (Casting).
     * Ini penting agar is_mock_location benar-benar dibaca sebagai boolean (true/false) oleh database.
     */
    protected $casts = [
        'is_mock_location' => 'boolean',
        'user_latitude' => 'double',
        'user_longitude' => 'double',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
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
        'notes',
        'attachment_path',
    ];
    
    protected $casts = [
        'is_mock_location' => 'boolean',
        'user_latitude' => 'double',
        'user_longitude' => 'double',
    ];

    protected $guarded = ['id'];

    // Relasi 1-to-1 (atau HasMany jika 1 absen bisa banyak lampiran)
    public function proof()
    {
        return $this->hasOne(AttendanceProof::class, 'attendance_id');
    }
}
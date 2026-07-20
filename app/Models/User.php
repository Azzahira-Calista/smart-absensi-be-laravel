<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// 1. TAMBAHKAN IMPORT INI DI ATAS:
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    // 2. TAMBAHKAN HasApiTokens DI DALAM SINI:
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'device_id',
        'satuan_kerja',
        'status_magang',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
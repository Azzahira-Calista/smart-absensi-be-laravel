<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->enum('action_type', ['CHECK_IN', 'CHECK_OUT']);
            $table->decimal('user_latitude', 10, 8);
            $table->decimal('user_longitude', 11, 8);
            $table->enum('status', ['SUCCESS', 'FAILED_OUTSIDE']);
            $table->boolean('is_mock_location')->default(false);
            $table->string('device_id');
            $table->timestamps(); // otomatis mencatat jam & tanggal server aman dari manipulasi HP
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
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

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->foreignId('location_id')
            ->nullable()
            ->constrained()
            ->nullOnDelete();

        $table->enum('action_type', [
            'CHECK_IN',
            'CHECK_OUT'
        ]);

        $table->enum('attendance_type', [
            'WFO',
            'WFH',
            'IZIN',
            'SAKIT'
        ]);

        $table->decimal('user_latitude',10,8)->nullable();

        $table->decimal('user_longitude',11,8)->nullable();

        $table->enum('status',[
            'SUCCESS',
            'FAILED_OUTSIDE',
            'PENDING_APPROVAL'
        ]);

        $table->boolean('is_mock_location')->default(false);

        $table->string('device_id');

        $table->text('notes')->nullable();

        $table->string('attachment_path')->nullable();

        $table->timestamps();
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Bundle $table) {
            // Menambahkan kolom setelah kolom action_type (atau sesuaikan posisinya)
            $table->string('attendance_type')->after('action_type')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Bundle $table) {
            $table->dropColumn('attendance_type');
        });
    }
};

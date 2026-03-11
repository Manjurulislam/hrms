<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->text('address')->nullable();
            $table->text('website')->nullable();
            $table->time('office_start')->nullable();
            $table->time('office_end')->nullable();
            $table->ipAddress('office_ip')->nullable();

            // Attendance settings
            $table->unsignedTinyInteger('work_hours')->default(8);
            $table->unsignedTinyInteger('half_day_hours')->default(4);
            $table->unsignedTinyInteger('late_grace')->default(15);
            $table->unsignedTinyInteger('early_grace')->default(15);
            $table->unsignedTinyInteger('max_sessions')->default(10);
            $table->unsignedTinyInteger('min_session_gap')->default(2);
            $table->unsignedTinyInteger('max_breaks')->default(5);
            $table->boolean('auto_close')->default(true);
            $table->time('auto_close_at')->nullable();
            $table->boolean('track_ip')->default(true);
            $table->boolean('track_location')->default(true);

            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

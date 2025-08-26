<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('department_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->index();
            $table->json('work_days');
            $table->timestamp('work_start_time')->index();
            $table->timestamp('work_end_time')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_schedules');
    }
};

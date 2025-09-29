<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->date('attendance_date')->index();

            // Scheduled office hours for the day
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            $table->integer('grace_minutes')->default(0);

            // Time tracking
            $table->time('first_check_in')->nullable();
            $table->time('last_check_out')->nullable();
            $table->integer('total_working_minutes')->default(0);
            $table->integer('total_break_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);

            // Session tracking
            $table->integer('total_sessions')->default(0);

            // Status
            $table->enum('status', [
                'present',
                'absent',
                'half_day',
                'late',
                'holiday',
                'weekend',
                'leave',
                'work_from_home'
            ])->default('absent')->index();

            // Working day and shift info
            $table->boolean('is_working_day')->default(true)->index();
            $table->string('shift_name')->nullable();

            // IP tracking for the day
            $table->json('ip_addresses')->nullable(); // Store all IPs used during the day
            $table->json('locations')->nullable(); // Store all locations during the day

            $table->timestamps();

            // Unique constraint - one summary per employee per day
            $table->unique(['employee_id', 'attendance_date']);

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

            // Additional indexes (with custom names to avoid length issues)
            $table->index(['attendance_date', 'status'], 'idx_summary_date_status');
            $table->index(['company_id', 'attendance_date'], 'idx_company_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};
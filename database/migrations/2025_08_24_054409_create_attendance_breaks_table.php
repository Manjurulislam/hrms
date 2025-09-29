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
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('attendance_session_id')->nullable()->index();
            $table->date('attendance_date')->index();

            // Break timing
            $table->dateTime('break_start');
            $table->dateTime('break_end')->nullable();
            $table->integer('duration_minutes')->nullable();

            // Break details
            $table->enum('break_type', ['lunch', 'tea', 'personal', 'prayer', 'other'])->default('personal');
            $table->text('reason')->nullable();

            // IP tracking
            $table->ipAddress('break_start_ip')->nullable();
            $table->ipAddress('break_end_ip')->nullable();

            $table->enum('status', ['active', 'completed'])->default('active');

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('attendance_session_id')->references('id')->on('attendance_sessions')->onDelete('cascade');

            // Indexes (with custom names to avoid length issues)
            $table->index(['employee_id', 'attendance_date'], 'idx_breaks_emp_date');
            $table->index(['employee_id', 'status'], 'idx_breaks_emp_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};
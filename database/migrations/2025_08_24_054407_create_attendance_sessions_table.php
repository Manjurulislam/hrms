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
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->integer('session_number')->default(1);

            // Check-in details
            $table->dateTime('check_in_time')->index();
            $table->time('scheduled_start_time')->nullable();
            $table->ipAddress('check_in_ip')->index();
            $table->string('check_in_location')->default('office');
            $table->decimal('check_in_lat', 10, 8)->nullable();
            $table->decimal('check_in_long', 11, 8)->nullable();
            $table->text('check_in_note')->nullable();

            // Check-out details
            $table->dateTime('check_out_time')->nullable()->index();
            $table->time('scheduled_end_time')->nullable();
            $table->ipAddress('check_out_ip')->nullable()->index();
            $table->string('check_out_location')->nullable();
            $table->decimal('check_out_lat', 10, 8)->nullable();
            $table->decimal('check_out_long', 11, 8)->nullable();
            $table->text('check_out_note')->nullable();

            // Session details
            $table->integer('duration_minutes')->nullable();
            $table->enum('session_type', ['regular', 'overtime', 'break_return'])->default('regular');
            $table->enum('status', ['active', 'completed', 'auto_closed'])->default('active')->index();

            // Tracking flags
            $table->boolean('is_late')->default(false)->index();
            $table->boolean('is_early_departure')->default(false)->index();
            $table->boolean('is_overtime')->default(false)->index();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

            // Indexes for performance (with custom names to avoid length issues)
            $table->index(['employee_id', 'attendance_date', 'session_number'], 'idx_emp_date_session');
            $table->index(['employee_id', 'status'], 'idx_emp_status');
            $table->index(['attendance_date', 'status'], 'idx_date_status');

            // Prevent multiple active sessions for same employee
            $table->unique(['employee_id', 'attendance_date', 'status'], 'unique_active_session');


            $table->date('attendance_date')->index();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};

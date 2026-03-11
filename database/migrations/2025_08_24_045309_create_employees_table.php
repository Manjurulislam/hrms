<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('id_no')->unique()->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('sec_phone', 20)->nullable();
            $table->string('nid')->unique()->nullable();
            $table->string('gender', 20)->nullable();

            $table->text('qualification')->nullable();
            $table->text('emergency_contact')->nullable();
            $table->string('blood_group', 20)->nullable()->index();
            $table->string('marital_status', 50)->nullable()->index();
            $table->string('bank_account')->nullable();

            $table->text('address')->nullable();

            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('manager_id')->nullable()->index();

            $table->string('emp_status', 20)->default('probation');
            $table->boolean('status')->default(true);
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

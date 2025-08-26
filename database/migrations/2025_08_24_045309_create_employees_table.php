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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('id_no')->unique()->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('sec_phone', 20)->nullable();
            $table->string('nid')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            $table->longText('qualification')->nullable();
            $table->text('emergency_contact')->nullable();
            $table->string('blood_group', 20)->nullable()->index();
            $table->string('marital_status', 50)->nullable()->index();
            $table->string('bank_account')->nullable();

            $table->text('address')->nullable();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('department_id')->index();

            $table->boolean('status')->default(true);
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('probation_end_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

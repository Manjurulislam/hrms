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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id')->index();

            $table->ipAddress()->index();
            $table->string('location')->default('office')->index();
            $table->string('lat')->nullable()->index();
            $table->string('long')->nullable()->index();
            $table->text('note')->nullable();

            $table->string('status', 50)->default('present')->index();
            $table->dateTime('checkin')->index();
            $table->dateTime('checkout')->nullable()->index();
            $table->date('attend_at')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

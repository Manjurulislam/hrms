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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->index();
            $table->text('notes');
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('leave_type_id')->index();
            $table->date('started_at')->index();
            $table->date('ended_at')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

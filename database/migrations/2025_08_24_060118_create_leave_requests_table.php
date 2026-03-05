<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->index();
            $table->text('notes')->nullable();
            $table->integer('total_days')->default(1);
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('current_approver_id')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->date('started_at')->index();
            $table->date('ended_at')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('current_approver_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

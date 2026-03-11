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
        Schema::create('approval_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('approver_type', 30);
            $table->unsignedBigInteger('approver_value')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->string('condition_type', 30)->default('always');
            $table->unsignedInteger('condition_value')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
    }
};

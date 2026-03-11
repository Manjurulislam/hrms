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
        Schema::table('leave_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('workflow_step_id')->nullable()->after('level');

            $table->foreign('workflow_step_id')
                ->references('id')->on('approval_workflow_steps')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_approvals', function (Blueprint $table) {
            $table->dropForeign(['workflow_step_id']);
            $table->dropColumn('workflow_step_id');
        });
    }
};

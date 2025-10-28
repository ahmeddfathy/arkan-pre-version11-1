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
        Schema::table('client_tickets', function (Blueprint $table) {
            // Add workflow stage field
            $table->enum('workflow_stage', [
                'received', 'triaged', 'assigned', 'in_development',
                'testing', 'waiting_approval', 'deployed', 'resolved', 'closed'
            ])->default('received')->after('status');

            // Add team lead field
            $table->foreignId('team_lead_id')->nullable()->constrained('users')->after('assigned_to');

            // Add estimated hours and actual hours
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('priority');
            $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours');

            // Add due date
            $table->timestamp('due_date')->nullable()->after('resolved_at');

            // Add tags field for better categorization
            $table->json('tags')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'workflow_stage', 'team_lead_id', 'estimated_hours',
                'actual_hours', 'due_date', 'tags'
            ]);
        });
    }
};

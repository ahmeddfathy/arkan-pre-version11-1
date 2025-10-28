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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('manager')->nullable()->after('client_id');
            $table->text('note')->nullable()->after('manager');
            $table->date('received_date')->nullable()->after('end_date');
            $table->date('preparation_start_date')->nullable()->after('received_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['manager', 'note', 'received_date', 'preparation_start_date']);
        });
    }
};

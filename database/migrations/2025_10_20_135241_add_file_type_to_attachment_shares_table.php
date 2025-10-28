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
        Schema::table('attachment_shares', function (Blueprint $table) {
            $table->string('file_type')->nullable()->after('description')->comment('نوع الملف المشارك');
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment_shares', function (Blueprint $table) {
            $table->dropIndex(['file_type']);
            $table->dropColumn('file_type');
        });
    }
};

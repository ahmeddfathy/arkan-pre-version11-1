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
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_attachment_id')->nullable()->after('uploaded_by');
            $table->foreign('parent_attachment_id')->references('id')->on('project_attachments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->dropForeign(['parent_attachment_id']);
            $table->dropColumn('parent_attachment_id');
        });
    }
};

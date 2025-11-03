<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {

            $table->string('revision_code', 50)->unique()->nullable()->after('id')
                ->comment('كود فريد للتعديل للتتبع والمراجعة');


            $table->index('revision_code', 'idx_revision_code');
        });
    }


    public function down(): void
    {
        Schema::table('task_revisions', function (Blueprint $table) {
            $table->dropIndex('idx_revision_code');
            $table->dropColumn('revision_code');
        });
    }
};

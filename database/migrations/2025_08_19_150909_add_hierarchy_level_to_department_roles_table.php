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
        Schema::table('department_roles', function (Blueprint $table) {
            $table->integer('hierarchy_level')->default(1)->after('role_id')->comment('مستوى الهرم الوظيفي: رقم أعلى = مستوى إداري أعلى');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_roles', function (Blueprint $table) {
            $table->dropColumn('hierarchy_level');
        });
    }
};

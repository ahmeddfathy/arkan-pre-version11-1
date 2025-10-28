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
        Schema::create('role_hierarchy', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->integer('hierarchy_level')->comment('المستوى الهرمي - كلما قل الرقم كان أعلى');
            $table->string('description')->nullable()->comment('وصف المستوى');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->unique('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_hierarchy');
    }
};

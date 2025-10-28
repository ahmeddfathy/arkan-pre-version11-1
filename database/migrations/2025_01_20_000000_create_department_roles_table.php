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
        Schema::create('department_roles', function (Blueprint $table) {
            $table->id();
            $table->string('department_name'); // اسم القسم
            $table->unsignedBigInteger('role_id'); // معرف الدور
            $table->timestamps();

            // Foreign key للـ role
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            // منع تكرار نفس القسم مع نفس الدور
            $table->unique(['department_name', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_roles');
    }
};

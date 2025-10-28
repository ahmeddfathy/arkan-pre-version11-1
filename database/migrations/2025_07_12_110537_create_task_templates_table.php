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
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('service_id'); // For which service
            $table->integer('estimated_hours')->default(0); // Estimated time in hours
            $table->integer('estimated_minutes')->default(0); // Estimated time in minutes
            $table->integer('order')->default(0); // For ordering tasks in the template
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('service_id')
                  ->references('id')
                  ->on('company_services')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};

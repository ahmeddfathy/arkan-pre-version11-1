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
        Schema::create('project_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->enum('delivery_type', ['مسودة', 'نهائي'])->comment('نوع التسليم');
            $table->timestamp('delivery_date')->comment('تاريخ التسليم');
            $table->unsignedBigInteger('delivered_by')->nullable()->comment('من قام بالتسليم');
            $table->text('notes')->nullable()->comment('ملاحظات التسليم');
            $table->timestamps();

            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('delivered_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['project_id', 'delivery_type']);
            $table->index('delivery_date');
            $table->index('delivery_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_deliveries');
    }
};

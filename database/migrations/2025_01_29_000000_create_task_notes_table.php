<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_notes', function (Blueprint $table) {
            $table->id();
            $table->enum('task_type', ['regular', 'template'])->comment('نوع المهمة');
            $table->unsignedBigInteger('task_user_id')->nullable()->comment('معرف مهمة المستخدم العادية');
            $table->unsignedBigInteger('template_task_user_id')->nullable()->comment('معرف مهمة المستخدم من القالب');
            $table->unsignedBigInteger('created_by')->comment('المستخدم الذي كتب الملاحظة');
            $table->text('content')->comment('محتوى الملاحظة');
            $table->timestamps();

            // Foreign keys
            $table->foreign('task_user_id')->references('id')->on('task_users')->onDelete('cascade');
            $table->foreign('template_task_user_id')->references('id')->on('template_task_user')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['task_type', 'task_user_id']);
            $table->index(['task_type', 'template_task_user_id']);
            $table->index('created_by');
            $table->index('created_at');

                        // Note: Constraint check will be handled at application level
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_notes');
    }
};

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
        Schema::create('revision_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained('task_revisions')->onDelete('cascade');
            $table->enum('assignment_type', ['executor', 'reviewer'])->comment('نوع التعيين: منفذ أو مراجع');
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('المستخدم السابق');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade')->comment('المستخدم الجديد');
            $table->foreignId('assigned_by_user_id')->constrained('users')->onDelete('cascade')->comment('من قام بالتعيين');
            $table->text('reason')->nullable()->comment('سبب إعادة التعيين');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('revision_id');
            $table->index('assignment_type');
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('assigned_by_user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_assignments');
    }
};

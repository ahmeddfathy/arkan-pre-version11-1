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
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade'); // الشخص اللي بيعمل follow
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade'); // الشخص اللي بيتم متابعته
            $table->timestamp('followed_at')->useCurrent();
            $table->timestamps();

            // منع المتابعة المكررة
            $table->unique(['follower_id', 'following_id']);

            // فهارس للأداء
            $table->index(['follower_id', 'followed_at']);
            $table->index(['following_id', 'followed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};

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
        Schema::create('badge_demotion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_badge_id')->constrained('badges')->onDelete('cascade');
            $table->foreignId('to_badge_id')->constrained('badges')->onDelete('cascade');
            $table->integer('demotion_levels')->default(1); // عدد المستويات التي سيتم تخفيضها
            $table->integer('points_percentage_retained')->default(50); // نسبة النقاط المحتفظ بها عند الهبوط
            $table->boolean('is_active')->default(true); // هل القاعدة مفعلة
            $table->text('description')->nullable(); // وصف لقاعدة الهبوط
            $table->timestamps();

            // تأكد من أن كل قاعدة هبوط فريدة من نوعها (من شارة معينة إلى شارة معينة)
            $table->unique(['from_badge_id', 'to_badge_id']);
        });

        // تعديل جدول نقاط الموسم لإضافة حقل للشارة الحالية
        Schema::table('user_season_points', function (Blueprint $table) {
            $table->foreignId('current_badge_id')->nullable()->after('total_points')
                  ->constrained('badges')->onDelete('set null');
            $table->foreignId('highest_badge_id')->nullable()->after('current_badge_id')
                  ->constrained('badges')->onDelete('set null');
            $table->foreignId('previous_season_badge_id')->nullable()->after('highest_badge_id')
                  ->constrained('badges')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_season_points', function (Blueprint $table) {
            $table->dropForeign(['current_badge_id']);
            $table->dropForeign(['highest_badge_id']);
            $table->dropForeign(['previous_season_badge_id']);
            $table->dropColumn(['current_badge_id', 'highest_badge_id', 'previous_season_badge_id']);
        });

        Schema::dropIfExists('badge_demotion_rules');
    }
};

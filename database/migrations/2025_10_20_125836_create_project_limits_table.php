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
        Schema::create('project_limits', function (Blueprint $table) {
            $table->id();

            // نوع الحد: company, department, team, user
            $table->enum('limit_type', ['company', 'department', 'team', 'user'])->index();

            // الشهر (nullable = حد عام لجميع الأشهر)
            $table->integer('month')->nullable()->comment('1-12 أو null للحد العام لجميع الشهور');

            // المعرف حسب النوع (nullable للشركة)
            $table->unsignedBigInteger('entity_id')->nullable()->comment('team_id أو user_id');
            $table->string('entity_name')->nullable()->comment('اسم القسم أو التيم أو المستخدم للعرض');

            // الحد الشهري
            $table->integer('monthly_limit')->default(0)->comment('عدد المشاريع المسموح بها');

            // ملاحظات
            $table->text('notes')->nullable();

            // تفعيل/تعطيل
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['limit_type', 'month']);
            $table->index(['limit_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_limits');
    }
};

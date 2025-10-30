<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة حقل ترتيب التنفيذ للخدمات (المستوى)
     */
    public function up(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            // رقم الترتيب/المستوى: 1 = أول خدمات تبدأ، 2 = تبدأ بعد انتهاء 1، إلخ
            $table->integer('execution_order')
                  ->default(1)
                  ->after('department')
                  ->comment('ترتيب التنفيذ: 1 = المستوى الأول (تبدأ أولاً)، 2 = المستوى الثاني (تبدأ بعد المستوى 1)، إلخ');

            $table->index('execution_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->dropIndex(['execution_order']);
            $table->dropColumn('execution_order');
        });
    }
};


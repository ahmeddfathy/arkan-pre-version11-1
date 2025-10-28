<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // الموظف الذي يتم تقييمه
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade'); // المدير أو الشخص الذي يقوم بالتقييم
            $table->string('evaluation_period')->nullable(); // مثل "الربع الأول 2023"
            $table->date('evaluation_date');
            $table->text('notes')->nullable(); // ملاحظات عامة على التقييم
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_evaluations');
    }
};

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {

  }


    public function boot(): void
    {
        // تسجيل Observers للنظام التلقائي للنقاط والشارات
        \App\Models\UserSeasonPoint::observe(\App\Observers\UserSeasonPointObserver::class);
        \App\Models\TaskUser::observe(\App\Observers\TaskUserObserver::class);
        \App\Models\TemplateTaskUser::observe(\App\Observers\TemplateTaskUserObserver::class);
        \App\Models\AdditionalTaskUser::observe(\App\Observers\AdditionalTaskUserObserver::class);

        // تسجيل Observer لأخطاء الموظفين
        \App\Models\EmployeeError::observe(\App\Observers\EmployeeErrorObserver::class);

        // تسجيل Observer لتتبع فترات التحضير
        \App\Models\Project::observe(\App\Observers\ProjectPreparationObserver::class);
    }
}

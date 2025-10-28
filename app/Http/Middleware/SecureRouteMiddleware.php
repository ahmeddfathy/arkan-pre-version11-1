<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class SecureRouteMiddleware
{
    /**
     * الـ Models اللي بتستخدم HasSecureId trait
     */
    protected $secureModels = [
        'user' => \App\Models\User::class,
        'userId' => \App\Models\User::class,
        'task' => \App\Models\Task::class,
        'project' => \App\Models\Project::class,
        'post' => \App\Models\Post::class,
        'comment' => \App\Models\Comment::class,
        'client' => \App\Models\Client::class,
        'team' => \App\Models\Team::class,
        'teamId' => \App\Models\Team::class,
        'meeting' => \App\Models\Meeting::class,
        'badge' => \App\Models\Badge::class,
        'season' => \App\Models\Season::class,
        'absence-request' => \App\Models\AbsenceRequest::class,
        'permission-request' => \App\Models\PermissionRequest::class,
        'additional-task' => \App\Models\AdditionalTask::class,
        'client-ticket' => \App\Models\ClientTicket::class,
        'skill' => \App\Models\Skill::class,
        'skill-category' => \App\Models\SkillCategory::class,
        'attendance' => \App\Models\Attendance::class,
        'work-shift' => \App\Models\WorkShift::class,
        'company-service' => \App\Models\CompanyService::class,
        'call-log' => \App\Models\CallLog::class,
        'overtime-request' => \App\Models\OverTimeRequests::class,
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحويل الـ route parameters من IDs عادية إلى secure IDs
        $this->transformRouteParameters($request);

        // تحويل الـ response لاستخدام secure IDs
        $response = $next($request);

        // تحويل الـ URLs في الـ response
        $this->transformResponseUrls($response);

        return $response;
    }

    /**
     * تحويل route parameters من secure IDs إلى IDs عادية للـ controllers
     */
    protected function transformRouteParameters(Request $request)
    {
        $currentRoute = Route::current();

        if (!$currentRoute) {
            return;
        }

        $parameters = $currentRoute->parameters();

        foreach ($parameters as $paramName => $paramValue) {
            // إذا كان الـ parameter عبارة عن secure ID وله model مقابل
            if (!is_numeric($paramValue) && isset($this->secureModels[$paramName])) {
                $modelClass = $this->secureModels[$paramName];

                // نحاول فك تشفير الـ secure ID
                if (method_exists($modelClass, 'findBySecureId')) {
                    $model = $modelClass::findBySecureId($paramValue);

                    if ($model) {
                        // نبدل الـ parameter بالـ ID العادي
                        $currentRoute->setParameter($paramName, $model->id);
                    }
                }
            }
        }
    }

    /**
     * تحويل URLs في الـ response لاستخدام secure IDs
     */
    protected function transformResponseUrls(Response $response)
    {
        if (!$response instanceof \Illuminate\Http\Response) {
            return;
        }

        $content = $response->getContent();

        if (!is_string($content)) {
            return;
        }

        // البحث عن URLs اللي فيها IDs عادية وتحويلها
        $modifiedContent = $this->replaceUrlsWithSecureIds($content);

        if ($modifiedContent !== $content) {
            $response->setContent($modifiedContent);
        }
    }

    /**
     * استبدال URLs بـ secure IDs
     */
    protected function replaceUrlsWithSecureIds($content)
    {
        // نبحث عن patterns مختلفة للـ URLs - أبسط patterns
        $patterns = [
            // URLs زي /users/123 أو /tasks/456
            '#(/users/(\d+))#',
            '#(/tasks/(\d+))#',
            '#(/projects/(\d+))#',
            '#(/posts/(\d+))#',
            '#(/comments/(\d+))#',
            '#(/clients/(\d+))#',
            '#(/teams/(\d+))#',
            '#(/meetings/(\d+))#',
            '#(/badges/(\d+))#',
            '#(/seasons/(\d+))#',
            '#(/employees/(\d+))#',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function ($matches) {
                $fullPath = $matches[1]; // مثل /users/123
                $id = $matches[2]; // مثل 123



                // نحدد نوع الـ model من الـ URL
                $modelClass = $this->getModelClassFromUrl($fullPath);

                if ($modelClass) {
                    try {
                        $model = $modelClass::find($id);

                        if ($model && method_exists($model, 'getSecureIdAttribute')) {
                            return str_replace("/{$id}", "/{$model->secure_id}", $fullPath);
                        }
                    } catch (\Exception $e) {
                        // إذا في مشكلة، نسيب الـ URL زي ما هو
                    }
                }

                return $matches[0]; // نرجع الـ match الأصلي
            }, $content);
        }

        return $content;
    }

    /**
     * تحديد الـ model class من الـ URL
     */
    protected function getModelClassFromUrl($url)
    {
        // معالجة خاصة لـ employees URLs
        if (strpos($url, '/employees/') !== false) {
            return \App\Models\User::class;
        }

        foreach ($this->secureModels as $paramName => $modelClass) {
            // نبحث عن اسم الـ model في الـ URL (مع أو بدون s)
            $patterns = [
                $paramName,
                $paramName . 's',
                str_replace('-', '', $paramName),
                str_replace('-', '', $paramName) . 's',
            ];

            foreach ($patterns as $pattern) {
                if (strpos($url, '/' . $pattern . '/') !== false) {
                    return $modelClass;
                }
            }
        }

        return null;
    }

    /**
     * تحقق إذا كان الـ route يحتاج تحويل
     */
    protected function shouldTransformRoute($routeName)
    {
        // Routes اللي مش محتاجة تحويل (APIs داخلية مثلاً)
        $excludedRoutes = [
            'debugbar.*',
            'telescope.*',
            'horizon.*',
            '_ignition.*',
        ];

        foreach ($excludedRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return false;
            }
        }

        return true;
    }
}

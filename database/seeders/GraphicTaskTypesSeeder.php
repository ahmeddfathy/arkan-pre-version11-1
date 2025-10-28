<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GraphicTaskType;

class GraphicTaskTypesSeeder extends Seeder
{
    public function run()
    {
        $graphicTaskTypes = [
            [
                'name' => 'دراسة تسويقية',
                'description' => 'إعداد دراسة تسويقية شاملة',
                'points' => 5,
                'min_minutes' => 25,
                'max_minutes' => 75,
                'average_minutes' => 50,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'دراسة فنية',
                'description' => 'إعداد دراسة فنية متخصصة',
                'points' => 2,
                'min_minutes' => 10,
                'max_minutes' => 30,
                'average_minutes' => 20,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'دراسة مالية',
                'description' => 'إعداد دراسة مالية تفصيلية',
                'points' => 2,
                'min_minutes' => 10,
                'max_minutes' => 30,
                'average_minutes' => 20,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'مؤشرات',
                'description' => 'تصميم وإعداد المؤشرات البصرية',
                'points' => 3,
                'min_minutes' => 15,
                'max_minutes' => 45,
                'average_minutes' => 30,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'تعديل',
                'description' => 'تعديلات طفيفة على التصاميم',
                'points' => 1,
                'min_minutes' => 5,
                'max_minutes' => 15,
                'average_minutes' => 10,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'تحديث مؤشرات',
                'description' => 'تحديث المؤشرات الموجودة',
                'points' => 1,
                'min_minutes' => 5,
                'max_minutes' => 15,
                'average_minutes' => 10,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'لوجو',
                'description' => 'تصميم شعار (لوجو) احترافي',
                'points' => 18,
                'min_minutes' => 90,
                'max_minutes' => 270,
                'average_minutes' => 180,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'غلاف خطة',
                'description' => 'تصميم غلاف للخطط والتقارير',
                'points' => 3,
                'min_minutes' => 15,
                'max_minutes' => 45,
                'average_minutes' => 30,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'غلاف استشاري',
                'description' => 'تصميم غلاف للخدمات الاستشارية',
                'points' => 4,
                'min_minutes' => 20,
                'max_minutes' => 60,
                'average_minutes' => 40,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'ترجمة',
                'description' => 'ترجمة النصوص والمحتوى',
                'points' => 5,
                'min_minutes' => 25,
                'max_minutes' => 75,
                'average_minutes' => 50,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'دعم ريف فني',
                'description' => 'دعم فني متخصص للمشاريع',
                'points' => 2,
                'min_minutes' => 10,
                'max_minutes' => 30,
                'average_minutes' => 20,
                'department' => 'التصميم',
                'is_active' => true,
            ],
            [
                'name' => 'دعم ريف تسويقي',
                'description' => 'دعم تسويقي للمشاريع والحملات',
                'points' => 1,
                'min_minutes' => 5,
                'max_minutes' => 15,
                'average_minutes' => 10,
                'department' => 'التصميم',
                'is_active' => true,
            ],
        ];

        foreach ($graphicTaskTypes as $taskType) {
            GraphicTaskType::create($taskType);
        }
    }
}

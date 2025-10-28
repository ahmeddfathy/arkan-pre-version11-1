<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\BadgeDemotionRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف البيانات الموجودة
        BadgeDemotionRule::truncate();
        Badge::truncate();

        // إضافة الشارات الأساسية
        $bronzeBadge = Badge::create([
            'name' => 'برونزي',
            'description' => 'المستوى البرونزي للموظفين',
            'icon' => 'bronze-badge.png',
            'color_code' => '#CD7F32',
            'required_points' => 0, // نقطة البداية
            'level' => 1,
        ]);

        $silverBadge = Badge::create([
            'name' => 'فضي',
            'description' => 'المستوى الفضي للموظفين',
            'icon' => 'silver-badge.png',
            'color_code' => '#C0C0C0',
            'required_points' => 1000,
            'level' => 2,
        ]);

        $goldBadge = Badge::create([
            'name' => 'ذهبي',
            'description' => 'المستوى الذهبي للموظفين',
            'icon' => 'gold-badge.png',
            'color_code' => '#FFD700',
            'required_points' => 3000,
            'level' => 3,
        ]);

        $platinumBadge = Badge::create([
            'name' => 'بلاتينيوم',
            'description' => 'المستوى البلاتيني للموظفين',
            'icon' => 'platinum-badge.png',
            'color_code' => '#E5E4E2',
            'required_points' => 7000,
            'level' => 4,
        ]);

        $conquerorBadge = Badge::create([
            'name' => 'كونكر',
            'description' => 'أعلى مستوى للموظفين',
            'icon' => 'conqueror-badge.png',
            'color_code' => '#8B0000',
            'required_points' => 15000,
            'level' => 5,
        ]);

        // إضافة قواعد الهبوط
        BadgeDemotionRule::create([
            'from_badge_id' => $conquerorBadge->id,
            'to_badge_id' => $platinumBadge->id,
            'demotion_levels' => 1,
            'points_percentage_retained' => 50, // الاحتفاظ بـ 50% من النقاط
            'is_active' => true,
            'description' => 'الهبوط من كونكر إلى بلاتينيوم مع بداية موسم جديد',
        ]);

        BadgeDemotionRule::create([
            'from_badge_id' => $platinumBadge->id,
            'to_badge_id' => $goldBadge->id,
            'demotion_levels' => 1,
            'points_percentage_retained' => 50,
            'is_active' => true,
            'description' => 'الهبوط من بلاتينيوم إلى ذهبي مع بداية موسم جديد',
        ]);

        BadgeDemotionRule::create([
            'from_badge_id' => $goldBadge->id,
            'to_badge_id' => $silverBadge->id,
            'demotion_levels' => 1,
            'points_percentage_retained' => 50,
            'is_active' => true,
            'description' => 'الهبوط من ذهبي إلى فضي مع بداية موسم جديد',
        ]);

        BadgeDemotionRule::create([
            'from_badge_id' => $silverBadge->id,
            'to_badge_id' => $bronzeBadge->id,
            'demotion_levels' => 1,
            'points_percentage_retained' => 50,
            'is_active' => true,
            'description' => 'الهبوط من فضي إلى برونزي مع بداية موسم جديد',
        ]);
    }
}

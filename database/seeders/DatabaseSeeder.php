<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run()
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // إضافة روابط أدوار الأقسام
        $this->call(DepartmentRolesSeeder::class);

        // إضافة الشارات
        $this->call(BadgesTableSeeder::class);

        // باقي السيدرز هنا...
    }
}

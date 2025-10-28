# 📌 إضافة رابط "مشاريعي" في القائمة

لإضافة رابط الصفحة الجديدة في قائمة التنقل، اتبع الخطوات التالية:

## 1. تحديد موقع ملف القائمة

الملف: `resources/views/navigation-menu.blade.php`

## 2. إضافة الرابط

أضف الكود التالي في المكان المناسب في القائمة:

```blade
<!-- مشاريعي -->
<x-nav-link href="{{ route('employee.projects.index') }}" :active="request()->routeIs('employee.projects.*')">
    <i class="fas fa-project-diagram me-2"></i>
    {{ __('مشاريعي') }}
</x-nav-link>
```

## 3. مثال كامل

إذا كنت تستخدم قائمة منسدلة، يمكنك إضافتها هكذا:

```blade
<!-- قسم المشاريع -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-project-diagram me-2"></i>
        المشاريع
    </a>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ route('projects.index') }}">
                <i class="fas fa-list me-2"></i>
                جميع المشاريع
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('employee.projects.index') }}">
                <i class="fas fa-user-check me-2"></i>
                مشاريعي
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ route('projects.create') }}">
                <i class="fas fa-plus me-2"></i>
                إضافة مشروع جديد
            </a>
        </li>
    </ul>
</li>
```

## 4. في القائمة الجانبية (Sidebar)

إذا كنت تستخدم sidebar:

```blade
<li class="sidebar-item {{ request()->routeIs('employee.projects.*') ? 'active' : '' }}">
    <a class="sidebar-link" href="{{ route('employee.projects.index') }}">
        <i class="fas fa-project-diagram"></i>
        <span>مشاريعي</span>
    </a>
</li>
```

## 5. في Dashboard الموظف

يمكنك أيضاً إضافة بطاقة سريعة في الـ Dashboard:

```blade
<div class="col-md-4">
    <div class="card text-center hover-shadow">
        <div class="card-body">
            <i class="fas fa-project-diagram fa-3x text-primary mb-3"></i>
            <h5 class="card-title">مشاريعي</h5>
            <p class="card-text">عرض وإدارة مشاريعي</p>
            <a href="{{ route('employee.projects.index') }}" class="btn btn-primary">
                عرض المشاريع
            </a>
        </div>
    </div>
</div>
```

## 6. الأيقونات المتاحة

يمكنك استخدام أي من الأيقونات التالية:
- `fa-project-diagram` - رسم بياني للمشاريع
- `fa-tasks` - قائمة المهام
- `fa-folder-open` - مجلد مفتوح
- `fa-briefcase` - حقيبة عمل
- `fa-clipboard-list` - قائمة
- `fa-user-check` - مستخدم مع علامة صح

---

**ملاحظة:** تأكد من أن المستخدم لديه صلاحية الوصول قبل عرض الرابط.

## 7. مع فحص الصلاحيات (اختياري)

```blade
@auth
    <x-nav-link href="{{ route('employee.projects.index') }}" :active="request()->routeIs('employee.projects.*')">
        <i class="fas fa-project-diagram me-2"></i>
        {{ __('مشاريعي') }}
    </x-nav-link>
@endauth
```


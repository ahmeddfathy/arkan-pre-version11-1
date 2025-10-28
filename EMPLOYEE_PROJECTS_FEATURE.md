# 📋 نظام إدارة حالات المشاريع للموظفين

## 📝 نظرة عامة

تم إضافة نظام متكامل لإدارة حالات المشاريع لكل موظف، يتيح للموظف رؤية جميع مشاريعه مع إمكانية:
- عرض الديدلاين (الموعد النهائي) لكل مشروع
- التحكم في حالة المشروع الخاصة به
- فلترة المشاريع حسب الموعد النهائي (اليوم، هذا الأسبوع، هذا الشهر، متأخر، قادم)
- فلترة المشاريع حسب الحالة
- عرض إحصائيات شاملة

---

## 🎯 الحالات المتاحة

تم إضافة 8 حالات مختلفة يمكن للموظف التحكم فيها:

| الحالة | الوصف | اللون |
|--------|-------|-------|
| `واقع ع التفريغ` | المشروع في مرحلة التفريغ | Warning (أصفر) |
| `واقع ع الإضافة` | المشروع في مرحلة الإضافة | Info (أزرق فاتح) |
| `واقع ع التعديل` | المشروع في مرحلة التعديل | Primary (أزرق) |
| `واقع ع مشكلة` | المشروع به مشكلة تحتاج حل | Danger (أحمر) |
| `تم تسليم مبدئي` | تم التسليم المبدئي للمشروع | Info (أزرق فاتح) |
| `جاري` | المشروع قيد التنفيذ (افتراضي) | Success (أخضر) |
| `تم تسليم نهائي` | تم التسليم النهائي | Success (أخضر) |
| `متوقف` | المشروع متوقف مؤقتاً | Secondary (رمادي) |

---

## 🗂️ الملفات المضافة/المعدلة

### 1. Migration
📁 `database/migrations/2025_10_26_100823_add_status_to_project_service_user_table.php`

إضافة حقل `status` من نوع `string` للجدول `project_service_user` مع قيمة افتراضية `جاري`.

### 2. Model
📁 `app/Models/ProjectServiceUser.php`

**إضافات:**
- Constants للحالات الثمانية
- Method `getAvailableStatuses()` - للحصول على جميع الحالات
- Method `updateStatus()` - لتحديث حالة المشروع
- Method `getStatusColor()` - للحصول على لون الحالة
- Scope `byStatus()` - للفلترة حسب الحالة
- Scope `deadlineThisMonth()` - للمشاريع المطلوبة هذا الشهر
- Scope `deadlineThisWeek()` - للمشاريع المطلوبة هذا الأسبوع
- Scope `deadlineToday()` - للمشاريع المطلوبة اليوم
- Scope `overdue()` - للمشاريع المتأخرة
- Scope `upcoming()` - للمشاريع القادمة
- Scope `forUser()` - للفلترة حسب المستخدم
- Scope `forTeam()` - للفلترة حسب الفريق
- Scope `forProject()` - للفلترة حسب المشروع

### 3. Controller
📁 `app/Http/Controllers/EmployeeProjectController.php`

**Methods:**
- `index()` - عرض قائمة المشاريع مع الفلاتر والإحصائيات
- `updateStatus()` - تحديث حالة المشروع (AJAX)
- `show()` - عرض تفاصيل مشروع معين
- `quickStats()` - إحصائيات سريعة (API)

### 4. View
📁 `resources/views/employee/projects/index.blade.php`

صفحة عرض حديثة وجذابة تحتوي على:
- **بطاقات إحصائية** تعرض:
  - إجمالي المشاريع
  - المشاريع الجارية
  - المشاريع المطلوبة هذا الأسبوع
  - المشاريع المطلوبة هذا الشهر
  - المشاريع المتأخرة
  - المشاريع المسلمة نهائياً

- **فلاتر متقدمة**:
  - فلتر حسب الحالة
  - فلتر حسب الموعد النهائي (اليوم، الأسبوع، الشهر، متأخر، قادم)
  - فلتر حسب المشروع
  - ترتيب حسب (الديدلاين، الحالة، اسم المشروع)

- **بطاقات المشاريع** تعرض:
  - اسم المشروع وكوده
  - الحالة (قابلة للتعديل مباشرة)
  - الخدمة
  - الموعد النهائي
  - نسبة المشاركة
  - الفريق
  - تحذيرات للمشاريع المتأخرة أو القريبة من الموعد النهائي

### 5. Routes
📁 `routes/web.php`

```php
Route::prefix('employee/projects')->name('employee.projects.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\EmployeeProjectController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\EmployeeProjectController::class, 'show'])->name('show');
    Route::post('/{id}/update-status', [App\Http\Controllers\EmployeeProjectController::class, 'updateStatus'])->name('update-status');
    Route::get('/quick-stats', [App\Http\Controllers\EmployeeProjectController::class, 'quickStats'])->name('quick-stats');
});
```

### 6. Project Model Update
📁 `app/Models/Project.php`

إضافة `projectServiceUsers()` كـ alias لـ `serviceParticipants()` للتوافق مع Controller.

---

## 📊 الإحصائيات المتوفرة

يعرض النظام إحصائيات مباشرة للموظف:
- إجمالي عدد المشاريع
- عدد المشاريع الجارية
- عدد المشاريع المطلوب تسليمها هذا الأسبوع
- عدد المشاريع المطلوب تسليمها هذا الشهر
- عدد المشاريع المتأخرة
- عدد المشاريع المسلمة نهائياً

---

## 🔧 كيفية الاستخدام

### للموظف:

1. **الوصول للصفحة**:
   ```
   /employee/projects
   ```

2. **تغيير حالة المشروع**:
   - اختر الحالة المناسبة من القائمة المنسدلة في بطاقة المشروع
   - يتم التحديث تلقائياً عبر AJAX

3. **الفلترة**:
   - استخدم الفلاتر في أعلى الصفحة
   - الفلترة تتم تلقائياً عند اختيار أي فلتر

4. **العرض**:
   - انقر على "عرض تفاصيل المشروع" لرؤية التفاصيل الكاملة

### في الكود:

```php
use App\Models\ProjectServiceUser;

// الحصول على مشاريع موظف معين
$projects = ProjectServiceUser::forUser($userId)->get();

// الحصول على المشاريع المتأخرة
$overdueProjects = ProjectServiceUser::forUser($userId)->overdue()->get();

// الحصول على مشاريع هذا الأسبوع
$weekProjects = ProjectServiceUser::forUser($userId)->deadlineThisWeek()->get();

// الحصول على المشاريع حسب الحالة
$inProgressProjects = ProjectServiceUser::forUser($userId)
    ->byStatus(ProjectServiceUser::STATUS_IN_PROGRESS)
    ->get();

// تحديث حالة مشروع
$projectUser->updateStatus(ProjectServiceUser::STATUS_INITIAL_DELIVERY);

// الحصول على لون الحالة
$color = $projectUser->getStatusColor();
```

---

## 🎨 التصميم

- تصميم حديث باستخدام **Gradient Glass Morphism**
- ألوان متدرجة جذابة
- استجابة كاملة لجميع الشاشات
- تأثيرات Hover ناعمة
- بطاقات تفاعلية
- SweetAlert2 للتنبيهات

---

## 🔒 الصلاحيات

- كل موظف يرى مشاريعه فقط
- تحديث الحالة متاح للموظف المعين في المشروع فقط
- المشاهدة محصورة بالموظف صاحب المشروع

---

## ⚙️ الإعدادات

### إضافة حالة جديدة:

1. أضف constant جديد في `ProjectServiceUser` Model:
```php
const STATUS_NEW_STATUS = 'الحالة الجديدة';
```

2. أضفها في `getAvailableStatuses()`:
```php
public static function getAvailableStatuses(): array
{
    return [
        // ... الحالات الموجودة
        self::STATUS_NEW_STATUS => 'الحالة الجديدة',
    ];
}
```

3. أضف اللون في `getStatusColor()`:
```php
public function getStatusColor(): string
{
    return match($this->status) {
        // ... الحالات الموجودة
        self::STATUS_NEW_STATUS => 'info',
        default => 'secondary'
    };
}
```

---

## 📱 API Endpoints

### الحصول على إحصائيات سريعة
```
GET /employee/projects/quick-stats
```

**Response:**
```json
{
    "today": 2,
    "this_week": 5,
    "overdue": 1,
    "in_progress": 8
}
```

### تحديث حالة المشروع
```
POST /employee/projects/{id}/update-status
```

**Request Body:**
```json
{
    "status": "تم تسليم مبدئي"
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم تحديث حالة المشروع بنجاح",
    "status": "تم تسليم مبدئي",
    "status_color": "info"
}
```

---

## 🚀 الميزات المستقبلية المقترحة

- [ ] إشعارات للمشاريع القريبة من الموعد النهائي
- [ ] تصدير المشاريع إلى Excel/PDF
- [ ] عرض Kanban Board للمشاريع
- [ ] عرض Timeline للمشاريع
- [ ] إضافة ملاحظات على كل حالة
- [ ] تاريخ تغيير الحالات
- [ ] إحصائيات متقدمة برسوم بيانية

---

## 📞 الدعم

إذا واجهت أي مشاكل أو لديك اقتراحات، يرجى التواصل مع فريق التطوير.

---

**تاريخ الإنشاء:** 26 أكتوبر 2025  
**الإصدار:** 1.0.0  
**الحالة:** ✅ مكتمل وجاهز للاستخدام


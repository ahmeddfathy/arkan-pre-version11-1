<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class SafeEncryptedCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        try {
            // محاولة فك التشفير
            return decrypt($value);
        } catch (DecryptException $e) {
            // إذا فشل فك التشفير، ارجع القيمة كما هي (غير مشفرة)
            Log::warning("Failed to decrypt field {$key} in model " . get_class($model) . ", returning original value");
            return $value;
        } catch (\Exception $e) {
            // أي خطأ آخر، ارجع القيمة الأصلية
            Log::warning("Error processing field {$key} in model " . get_class($model) . ": " . $e->getMessage());
            return $value;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        try {
            // تشفير القيمة الجديدة
            return encrypt($value);
        } catch (\Exception $e) {
            // في حالة فشل التشفير، احفظ القيمة كما هي
            Log::error("Failed to encrypt field {$key} in model " . get_class($model) . ": " . $e->getMessage());
            return $value;
        }
    }
}

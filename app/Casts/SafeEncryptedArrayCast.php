<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class SafeEncryptedArrayCast implements CastsAttributes
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
            // محاولة فك التشفير ثم تحويل إلى array
            $decrypted = decrypt($value);
            return is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
        } catch (DecryptException $e) {
            // إذا فشل فك التشفير، جرب تحويل القيمة كـ JSON عادي
            Log::warning("Failed to decrypt array field {$key} in model " . get_class($model) . ", trying as regular JSON");
            try {
                return json_decode($value, true) ?: [];
            } catch (\Exception $jsonException) {
                Log::warning("Failed to decode JSON for field {$key}, returning empty array");
                return [];
            }
        } catch (\Exception $e) {
            // أي خطأ آخر، جرب كـ JSON عادي
            Log::warning("Error processing array field {$key} in model " . get_class($model) . ": " . $e->getMessage());
            try {
                return json_decode($value, true) ?: [];
            } catch (\Exception $jsonException) {
                return [];
            }
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
            // تحويل إلى JSON ثم تشفير
            $jsonValue = is_array($value) ? json_encode($value) : $value;
            return encrypt($jsonValue);
        } catch (\Exception $e) {
            // في حالة فشل التشفير، احفظ كـ JSON عادي
            Log::error("Failed to encrypt array field {$key} in model " . get_class($model) . ": " . $e->getMessage());
            return is_array($value) ? json_encode($value) : $value;
        }
    }
}

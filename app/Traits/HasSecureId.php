<?php

namespace App\Traits;

use App\Services\SecureIdService;

trait HasSecureId
{
    /**
     * إرجاع الـ secure ID للنموذج
     */
    public function getSecureIdAttribute()
    {
        return SecureIdService::getInstance()->encode($this->id);
    }

    /**
     * إرجاع الـ ID العادي للنموذج (للاستخدام الداخلي)
     */
    public function getRawIdAttribute()
    {
        return $this->attributes['id'];
    }

    /**
     * البحث عن نموذج باستخدام secure ID
     */
    public static function findBySecureId($secureId)
    {
        $realId = SecureIdService::getInstance()->decode($secureId);

        if ($realId === null) {
            return null;
        }

        return static::find($realId);
    }

    /**
     * البحث عن نموذج باستخدام secure ID أو فشل
     */
    public static function findBySecureIdOrFail($secureId)
    {
        $model = static::findBySecureId($secureId);

        if ($model === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
        }

        return $model;
    }

    /**
     * scope للبحث باستخدام secure IDs متعددة
     */
    public function scopeWhereSecureIds($query, array $secureIds)
    {
        $realIds = SecureIdService::getInstance()->decodeMultiple($secureIds);

        return $query->whereIn('id', $realIds);
    }

    /**
     * إنشاء URL آمن للنموذج
     */
    public function secureUrl($route, $parameters = [])
    {
        return route($route, array_merge(['id' => $this->secure_id], $parameters));
    }

    /**
     * تخصيص Route Key للاستخدام في الـ routes
     */
    public function getRouteKeyName()
    {
        return 'id'; // نحتفظ بـ 'id' كما هو
    }

    /**
     * تخصيص Route Key Value
     */
    public function getRouteKey()
    {
        return $this->secure_id; // نرجع الـ secure ID في الـ routes
    }

    /**
     * حل الـ Route Binding
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // إذا كان الـ value رقم عادي، نبحث به مباشرة (للاستخدام الداخلي)
        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        // إذا كان hash، نفك التشفير ونبحث
        $realId = SecureIdService::getInstance()->decode($value);

        if ($realId !== null) {
            return $this->where('id', $realId)->first();
        }

        // معالجة الروابط المدمجة (مثل: secureId1 + secureId2)
        // نحاول تقسيم الرابط إلى أجزاء والبحث عن كل جزء
        $service = SecureIdService::getInstance();

        // طول الـ Secure ID العادي هو 30 حرف
        $secureIdLength = 30;

        if (strlen($value) > $secureIdLength) {
            // نحاول تقسيم الرابط إلى أجزاء من 30 حرف
            $validParts = [];
            $validMeetings = [];

            for ($i = 0; $i < strlen($value); $i += $secureIdLength) {
                $part = substr($value, $i, $secureIdLength);
                if (strlen($part) == $secureIdLength) {
                    $decodedId = $service->decode($part);
                    if ($decodedId !== null) {
                        // التحقق من وجود الاجتماع في قاعدة البيانات
                        $meeting = $this->where('id', $decodedId)->first();
                        if ($meeting) {
                            $validParts[] = $part;
                            $validMeetings[] = $meeting;
                        }
                    }
                }
            }

            // إذا وجدنا اجتماع واحد صالح، نعود به
            if (count($validMeetings) == 1) {
                return $validMeetings[0];
            }

            // إذا وجدنا أكثر من اجتماع، نحاول اختيار الأكثر منطقية
            if (count($validMeetings) > 1) {
                // نعود بالاجتماع الأحدث (الأكبر ID)
                $latestMeeting = collect($validMeetings)->sortByDesc('id')->first();
                return $latestMeeting;
            }
        }

        return null;
    }

    /**
     * تخصيص toArray للتأكد من إرجاع secure_id في JSON
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['secure_id'] = $this->secure_id;
        return $array;
    }

    /**
     * تخصيص attributesToArray للتأكد من إرجاع secure_id في Blade
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $attributes['secure_id'] = $this->secure_id;
        return $attributes;
    }
}

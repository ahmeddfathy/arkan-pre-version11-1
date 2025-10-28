<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class EncryptedUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           (count($credentials) === 1 &&
            Str::contains($this->firstCredentialKey($credentials), 'password'))) {
            return;
        }

        // Build the query to search all users
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }

            // للبحث في الحقول المشفرة مثل الإيميل
            if (in_array($key, ['email', 'national_id_number', 'phone_number'])) {
                // استخدام الدوال المساعدة الجديدة في User model
                switch ($key) {
                    case 'email':
                        return $this->model::findByEmail($value);
                    case 'national_id_number':
                        return $this->model::findByNationalId($value);
                    case 'phone_number':
                        return $this->model::findByPhone($value);
                }

                // إذا لم نجد المستخدم، ارجع null
                return null;
            } else {
                // للحقول غير المشفرة، استخدم البحث العادي
                if (is_array($value) || $value instanceof Arrayable) {
                    $query->whereIn($key, $value);
                } else {
                    $query->where($key, $value);
                }
            }
        }

        // إذا كانت جميع الحقول غير مشفرة، استخدم البحث العادي
        if (!in_array('email', array_keys($credentials)) &&
            !in_array('national_id_number', array_keys($credentials)) &&
            !in_array('phone_number', array_keys($credentials))) {
            return $query->first();
        }

        return null;
    }

    /**
     * Get the first key from the credential array.
     *
     * @param  array  $credentials
     * @return string|null
     */
    protected function firstCredentialKey(array $credentials)
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }

        return null;
    }
}

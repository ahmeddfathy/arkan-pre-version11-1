<?php

namespace App\Services;

use Hashids\Hashids;

class SecureIdService
{
    private static $instance = null;
    private $hashids;

    private function __construct()
    {
        // استخدم salt قوي ومميز لمشروعك
        $salt = config('app.key', 'default-salt-for-secure-ids');
        $minLength = 30; // طول أدنى للـ hash
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

        $this->hashids = new Hashids($salt, $minLength, $alphabet);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * تشفير ID إلى hash
     */
    public function encode($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return null;
        }

        return $this->hashids->encode($id);
    }

    /**
     * فك تشفير hash إلى ID
     */
    public function decode($hash)
    {
        if (empty($hash) || !is_string($hash)) {
            return null;
        }

        $decoded = $this->hashids->decode($hash);

        return isset($decoded[0]) ? $decoded[0] : null;
    }

    /**
     * التحقق من صحة الـ hash
     */
    public function isValidHash($hash)
    {
        if (empty($hash) || !is_string($hash)) {
            return false;
        }

        $decoded = $this->decode($hash);
        return $decoded !== null && is_numeric($decoded);
    }

    /**
     * تشفير مجموعة من الـ IDs
     */
    public function encodeMultiple(array $ids)
    {
        $encoded = [];
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $encoded[] = $this->encode($id);
            }
        }
        return $encoded;
    }

    /**
     * فك تشفير مجموعة من الـ hashes
     */
    public function decodeMultiple(array $hashes)
    {
        $decoded = [];
        foreach ($hashes as $hash) {
            $id = $this->decode($hash);
            if ($id !== null) {
                $decoded[] = $id;
            }
        }
        return $decoded;
    }
}

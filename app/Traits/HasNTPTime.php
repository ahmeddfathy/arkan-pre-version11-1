<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait HasNTPTime
{
    /**
     * الحصول على الوقت الحالي من NTP servers مع Cache
     *
     * @param string $timezone التوقيت المطلوب (افتراضياً Africa/Cairo)
     * @param bool $logDetails هل يتم تسجيل التفاصيل في الـ Log
     * @return Carbon
     */
    protected function getCurrentTimeFromNTP(string $timezone = 'Africa/Cairo', bool $logDetails = true): Carbon
    {
        // استخدام Cache لمدة 30 ثانية لتجنب استدعاء NTP في كل مرة
        $cacheKey = 'ntp_time_' . $timezone;

        $cachedTime = Cache::get($cacheKey);

        if ($cachedTime) {
            // إرجاع الوقت المحفوظ مع إضافة الوقت الفعلي الذي مر
            $cachedTimestamp = $cachedTime['timestamp'];
            $cachedAt = $cachedTime['cached_at'];
            $secondsPassed = time() - $cachedAt;

            return Carbon::createFromTimestamp($cachedTimestamp + $secondsPassed)->setTimezone($timezone);
        }

        try {
            $ntpTime = null;
            $successfulServer = null;

            $ntpServers = [
                'time.google.com',
                'pool.ntp.org',
                'time.windows.com',
                'time.apple.com'
            ];

            foreach ($ntpServers as $server) {
                try {
                    // تقليل timeout من 1 ثانية إلى 0.5 ثانية
                    $socket = @fsockopen("udp://$server", 123, $errno, $errstr, 0.5);

                    if ($socket) {
                        // تعيين timeout للقراءة
                        stream_set_timeout($socket, 0, 500000); // 0.5 second

                        $msg = "\010" . str_repeat("\0", 47);
                        fwrite($socket, $msg);

                        $response = fread($socket, 48);
                        fclose($socket);

                        if (strlen($response) == 48) {
                            $data = unpack('N', substr($response, 40, 4));
                            $ntpTime = $data[1] - 2208988800;
                            $successfulServer = $server;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    if ($logDetails) {
                        Log::debug("NTP server {$server} failed: " . $e->getMessage());
                    }
                    continue;
                }
            }

            if ($ntpTime) {
                $now = Carbon::createFromTimestamp($ntpTime);

                // حفظ في Cache لمدة 30 ثانية
                Cache::put($cacheKey, [
                    'timestamp' => $ntpTime,
                    'cached_at' => time()
                ], 30);

                if ($logDetails) {
                    Log::info('NTP time fetched successfully', [
                        'server' => $successfulServer,
                        'ntp_time' => $now->setTimezone($timezone)->format('Y-m-d H:i:s'),
                        'server_time' => Carbon::now($timezone)->format('Y-m-d H:i:s'),
                        'timezone' => $timezone,
                        'caller' => get_class($this)
                    ]);
                }

                return $now->setTimezone($timezone);
            }
        } catch (\Exception $e) {
            if ($logDetails) {
                Log::error('NTP time fetch failed', [
                    'error' => $e->getMessage(),
                    'caller' => get_class($this)
                ]);
            }
        }

        // Fallback to server time
        if ($logDetails) {
            Log::warning('Using server time as fallback', [
                'timezone' => $timezone,
                'caller' => get_class($this)
            ]);
        }

        return Carbon::now($timezone);
    }

    /**
     * الحصول على الوقت الحالي بتوقيت القاهرة من NTP
     *
     * @return Carbon
     */
    protected function getCurrentCairoTime(): Carbon
    {
        return $this->getCurrentTimeFromNTP('Africa/Cairo');
    }

    /**
     * الحصول على الوقت الحالي بتوقيت UTC من NTP
     *
     * @return Carbon
     */
    protected function getCurrentUTCTime(): Carbon
    {
        return $this->getCurrentTimeFromNTP('UTC');
    }

    /**
     * فحص الفرق بين وقت NTP ووقت السيرفر
     *
     * @param string $timezone
     * @return array ['difference_seconds' => int, 'ntp_time' => string, 'server_time' => string]
     */
    protected function checkNTPTimeDifference(string $timezone = 'Africa/Cairo'): array
    {
        $ntpTime = $this->getCurrentTimeFromNTP($timezone, false);
        $serverTime = Carbon::now($timezone);

        $differenceSeconds = $ntpTime->diffInSeconds($serverTime, false);

        return [
            'difference_seconds' => $differenceSeconds,
            'ntp_time' => $ntpTime->format('Y-m-d H:i:s'),
            'server_time' => $serverTime->format('Y-m-d H:i:s'),
            'is_synced' => abs($differenceSeconds) <= 5, // متزامن إذا كان الفرق أقل من 5 ثواني
            'timezone' => $timezone
        ];
    }
}


<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // كل Http Client requests هتشتغل بالشهادة تلقائي
        Http::macro('withClientCert', function () {
            return Http::withOptions([
                'curl' => [
                    CURLOPT_SSLCERT => '/home/u885830099/ssl/client.p12', // المسار على Hostinger
                    CURLOPT_SSLCERTPASSWD => 'AhmedArkan', // الباسورد اللي اخترته وقت export
                    CURLOPT_SSLCERTTYPE => 'P12',
                ],
                'verify' => '/home/u885830099/ssl/myCA.pem', // للتحقق من CA
            ]);
        });
    }
}

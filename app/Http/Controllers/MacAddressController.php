<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MacAddressController extends Controller
{
    public function getMacAddresses()
    {
        $deviceMac = exec('getmac');
        $routerMac = $this->getRouterMacAddress();
        $isConnected = $this->isUserConnectedToRouter();

        return response()->json([
            'device_mac' => $deviceMac,
            'router_mac' => $routerMac,
            'is_connected_to_router' => $isConnected,
        ]);
    }

    private function getRouterMacAddress()
    {
        $output = [];
        exec('arp -a', $output);
        foreach ($output as $line) {
            if (str_contains($line, '192.168.1.1')) {
                preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $line, $matches);
                return $matches[0] ?? 'Unknown';
            }
        }
        return 'Not Found';
    }

    private function isUserConnectedToRouter()
    {
        $localIp = $this->getLocalIpAddress();
        $routerIp = '192.168.1.1';
        return $this->isSameSubnet($localIp, $routerIp);
    }

    private function getLocalIpAddress()
    {
        return gethostbyname(gethostname());
    }

    private function isSameSubnet($localIp, $routerIp)
    {
        $localBinary = ip2long($localIp);
        $routerBinary = ip2long($routerIp);
        $subnetMask = ip2long('255.255.255.0');
        return ($localBinary & $subnetMask) === ($routerBinary & $subnetMask);
    }
}

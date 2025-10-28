<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use GeoIp2\Exception\AddressNotFoundException;

class GeoController extends Controller
{
    /**
     * Display geolocation information for the current user's IP
     */
    public function index(Request $request)
    {
        // Get the user's IP address
        $ip = $request->ip();

        // Get location data for the IP
        $location = $this->getGeoIpLocation($ip);
        $location->visitor_ip = $ip; // Add the visitor's IP to the location object

        return view('geo.index', compact('location'));
    }

    /**
     * Check a specific IP address
     */
    public function checkIp(Request $request)
    {
        // Validate IP address
        $request->validate([
            'ip' => 'required|ip',
        ]);

        $ip = $request->input('ip');
        $visitor_ip = $request->ip(); // The actual visitor's IP

        // Log for debugging
        Log::info('Checking IP: ' . $ip);

        // Get location data for the IP
        $location = $this->getGeoIpLocation($ip);
        $location->visitor_ip = $visitor_ip; // Store the actual visitor's IP
        $location->checked_ip = $ip; // Store the IP that was checked

        // Force refresh the page with the new data
        return view('geo.index', compact('location'));
    }

    /**
     * API endpoint to get location data as JSON
     */
    public function getLocationData(Request $request)
    {
        $ip = $request->input('ip', $request->ip());
        $visitor_ip = $request->ip();

        $location = $this->getGeoIpLocation($ip);
        $location->visitor_ip = $visitor_ip;

        return response()->json($location);
    }

    /**
     * Get GeoIP location data from multiple sources
     */
    private function getGeoIpLocation($ip)
    {
        // Try MaxMind GeoIP database first
        try {
            $location = geoip()->getLocation($ip);
            return $location;
        } catch (AddressNotFoundException $e) {
            Log::warning("IP not found in GeoIP database: {$ip}. Trying fallback service.");

            // Fallback to IP-API.com (free service)
            try {
                $response = Http::get("http://ip-api.com/json/{$ip}");

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data['status'] === 'success') {
                        // Create a location object similar to GeoIP's format
                        $location = new \stdClass();
                        $location->ip = $ip;
                        $location->iso_code = $data['countryCode'] ?? '';
                        $location->country = $data['country'] ?? '';
                        $location->city = $data['city'] ?? '';
                        $location->state = $data['region'] ?? '';
                        $location->state_name = $data['regionName'] ?? '';
                        $location->postal_code = $data['zip'] ?? '';
                        $location->lat = $data['lat'] ?? 0;
                        $location->lon = $data['lon'] ?? 0;
                        $location->timezone = $data['timezone'] ?? '';
                        $location->continent = ''; // IP-API doesn't provide continent
                        $location->currency = ''; // IP-API doesn't provide currency
                        $location->default = false;
                        $location->from_api = true; // Flag that this came from the API

                        return $location;
                    }
                }

                Log::error("Fallback IP service failed for IP: {$ip}");
            } catch (\Exception $ex) {
                Log::error("Error using fallback IP service: " . $ex->getMessage());
            }

            // If all else fails, use default location
            $location = geoip()->getLocation('127.0.0.1');
            $location->not_found = true;
            return $location;
        }
    }
}

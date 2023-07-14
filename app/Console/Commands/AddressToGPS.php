<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\GpsCoord;
use App\Models\Address;

class AddressToGPS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:lookup {--addressid= } {--line1=} {--line2=} {--city=} {--zip=} {--state=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Looks up the GPS coordinates for an address and updates the database if necessary';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $addressId = $this->option('addressid');
        if (empty($addressId)) {
            $this->info('Lookup up existing address by address');
            $line1 = $this->option('line1');
            $line2 = $this->option('line2');
            $city = $this->option('city');
            $state = $this->option('state');
            $zip = $this->option('zip');
            $addr = Address::where('line_1', $line1)
                ->where('line_2', $line2)
                ->where('city', $city)
                ->where('state_province', $state)
                ->where('zip', $zip)
                ->first();
        } else {
            $this->info('Looking up existing address by id');
            $addr = Address::find($addressId);
            if ($addr) {
                $this->info('Address found');
                $line1 = $addr->line_1;
                //$line2 = $addr->line_2;
                $city = $addr->city;
                $state = $addr->state_province;
                $zip = $addr->zip;
            } else {
                $this->error('Could not locate that address in the database');
                return 42;
            }
        }

        $gps_coords = null;
        if ($addr) {
            $gps_coords = $addr->gps_coordinates;
        }

        if ($gps_coords) {
            $this->info('Location already resolved as:');
            $this->line($gps_coords->coords);
        } else {
            $this->info('No GPS, resolving...');
            $geo = $this->getGoogleGeoInfo($line1, $city, $state, $zip);

            if ($geo !== null && isset($geo->status) && $geo->status == 'OK') {
                $this->info('GPS is valid');
                $lat1 = floatval($geo->results[0]->geometry->location->lat); // Latitude
                $lon1 = floatval($geo->results[0]->geometry->location->lng);

                if ($addr && empty($gps_coords)) {
                    $gpsAddr = new GpsCoord();
                    $gpsAddr->created_at = now('America/Chicago');
                    $gpsAddr->updated_at = now('America/Chicago');
                    $gpsAddr->coords = $lat1 . ',' . $lon1;
                    $gpsAddr->ref_type_id = 5;
                    $gpsAddr->gps_coord_type_id = 7;
                    $gpsAddr->type_id = $addr->id;
                    $gpsAddr->save();
                }
                $this->info('Location resolved to:');
                $this->line(json_encode($geo, \JSON_PRETTY_PRINT));
            } else {
                $this->info('GPS is not valid');
                $this->line(json_encode($geo));
            }
        }
    }

    protected function getGoogleGeoInfo($address1, $city, $state, $zip)
    {
        $address = implode(', ', [$address1, $city, $state]);
        $address = urlencode($address);
        $restrict = '&components=postal_code:' . $zip;

        $apiKey = 'AIzaSyCDt4kkV8MU9UARMDkfnbVHcQ8uLIscMB0'; // Google maps now requires an API key.
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=' . $apiKey . $restrict;
        $cacheKey = 'google-geo-lookup-' . md5($url);
        return Cache::remember($cacheKey, 60 * 24, function () use ($url) {
            $client = new Client();
            try {
                $res = $client->get($url);
                if ($res->getStatusCode() == 200) {
                    $this->info('Got a good response from google');
                    return json_decode((string) $res->getBody());
                } else {
                    $this->info('got a bad response from google');
                    return null;
                }
            } catch (\Exception $e) {
                info('Exception while asking google for ' . $url, [$e]);
                return null;
            }
        });
    }
}

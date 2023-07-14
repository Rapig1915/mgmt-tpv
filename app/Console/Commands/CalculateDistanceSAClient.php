<?php

namespace App\Console\Commands;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Carbon\CarbonImmutable;
use App\Models\StatsProduct;
use App\Models\GpsDistance;
use App\Models\GpsCoord;
use App\Models\Event;
use App\Models\Address;

class CalculateDistanceSAClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:distance:sa:client {--confcode= } {--start_date=} {--end_date=} {--nightly}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the distance between the Sales Agent and the client';

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
        $confcode = $this->hasOption('confcode') ? $this->option('confcode') : null;
        if ($confcode === null) {
            $start_date = ($this->option('start_date'))
                ? CarbonImmutable::parse($this->option('start_date'))
                : CarbonImmutable::now('America/Chicago')->subMinutes(30);
            $end_date = ($this->option('end_date'))
                ? CarbonImmutable::parse($this->option('end_date'))
                : CarbonImmutable::now('America/Chicago');

            if ($this->option('nightly')) {
                $start_date = $end_date->subDay()->startOfDay();
                $end_date = $end_date->subDay()->endOfDay();
            }

            $sp = StatsProduct::select(
                'stats_product.gps_coords',
                'stats_product.event_id',
                'stats_product.service_address1',
                'stats_product.service_address2',
                'stats_product.service_city',
                'stats_product.service_state',
                'stats_product.service_zip',
                'stats_product.eztpv_id'
            )->leftJoin(
                'events',
                'stats_product.event_id',
                'events.id'
            )->whereNotNull(
                'stats_product.eztpv_id'
            )->whereBetween(
                'stats_product.event_created_at',
                [
                    $start_date,
                    $end_date
                ]
            )->where(
                'events.sa_distance',
                0
            )->where(
                'events.channel_id',
                1
            )->get();
        } else {
            //by conf code
            $sp = StatsProduct::select(
                'stats_product.gps_coords',
                'stats_product.event_id',
                'stats_product.service_address1',
                'stats_product.service_address2',
                'stats_product.service_city',
                'stats_product.service_state',
                'stats_product.service_zip',
                'stats_product.eztpv_id'
            )->join(
                'events',
                'stats_product.event_id',
                'events.id'
            )->whereNotNull(
                'stats_product.gps_coords'
            )->where(
                'stats_product.confirmation_code',
                $confcode
            )->groupBy('stats_product.event_id')
                ->get();
        }

        foreach ($sp as $sproduct) {
            $address = implode(', ', [$sproduct->service_address1, $sproduct->service_city, $sproduct->service_state]);
            $address = urlencode($address);
            $components = '&components=postal_code:' . $sproduct->service_zip;

            $addr = Address::where('line_1', $sproduct->service_address1)
                ->where('line_2', $sproduct->service_address2)
                ->where('city', $sproduct->service_city)
                ->where('zip', $sproduct->service_zip)
                ->with('gps_coordinates')
                ->first();

            $lat1 = null;
            $lon1 = null;

            $gpsAddr = null;

            if (!empty($addr) && !empty($addr->gps_coordinates)) {
                $gpsAddr = $addr->gps_coordinates;
                $looked_up_loc = explode(',', $gpsAddr->coords);
                if (count($looked_up_loc) == 2) {
                    $lat1 = floatval($looked_up_loc[0]);
                    $lon1 = floatval($looked_up_loc[1]);
                }
            }

            if ($lat1 == null || $lon1 == null) {
                $geo = $this->getGoogleGeoInfo($address, $components);
                if ($geo !== null) {
                    if (isset($geo->status) && $geo->status == 'OK') {
                        $lat1 = floatval($geo->results[0]->geometry->location->lat); // Latitude
                        $lon1 = floatval($geo->results[0]->geometry->location->lng);

                        if ($addr) {
                            $gpsAddr = new GpsCoord();
                            $gpsAddr->created_at = now('America/Chicago');
                            $gpsAddr->updated_at = now('America/Chicago');
                            $gpsAddr->coords = $lat1 . ',' . $lon1;
                            $gpsAddr->ref_type_id = 5;
                            $gpsAddr->gps_coord_type_id = 7;
                            $gpsAddr->type_id = $addr->id;
                            $gpsAddr->save();
                        }
                    } else {
                        Log::error('Non-OK geolocate status for event: ' . $sproduct->event_id, [!empty($geo->status) ? $geo->status : '$geo is empty']);
                    }
                } else {
                    Log::error('Bad status code for GET request event_id:' . $sproduct->event_id . 'on address' . $address);
                }
            }

            if ($lat1 != null && $lon1 != null && !empty($sproduct->gps_coords)) {
                $gps_coords = explode(',', $sproduct->gps_coords);
                $lat2 = floatval($gps_coords[0]);
                $lon2 = floatval($gps_coords[1]);
                $agentLoc = GpsCoord::where('ref_type_id', 1)->where('gps_coord_type_id', 2)->where('type_id', $sproduct->eztpv_id)->first();
                if (empty($agentLoc)) {
                    $agentLoc = new GpsCoord();
                    $agentLoc->created_at = now('America/Chicago');
                    $agentLoc->updated_at = now('America/Chicago');
                    $agentLoc->ref_type_id = 1;
                    $agentLoc->type_id = $sproduct->eztpv_id;
                    $agentLoc->gps_coord_type_id = 2;
                    $agentLoc->coords = $sproduct->gps_coords;
                    $agentLoc->save();
                }
                $dis = $this->distance($lat1, $lon1, $lat2, $lon2);
                $e = Event::find($sproduct->event_id);
                if ($e) {
                    $e->sa_distance = $dis;
                    $e->save();

                    if ($gpsAddr && $agentLoc) {
                        $disRec = GpsDistance::where('type_id', $e->id)->where('ref_type_id', 3)->where('distance_type_id', 1)->first();
                        if (empty($disRec)) {
                            info('Added gps_distance (SA) entry for event ' . $sproduct->event_id);
                            $disRec = new GpsDistance();
                            $disRec->created_at = now('America/Chicago');
                            $disRec->updated_at = now('America/Chicago');
                            $disRec->type_id = $e->id;
                            $disRec->ref_type_id = 3;
                            $disRec->distance_type_id = 1;
                            $disRec->gps_point_a = $gpsAddr->id;
                            $disRec->gps_point_b = $agentLoc->id;
                            $disRec->distance = $dis;
                            $disRec->save();
                        } else {
                            info('Updated gps_distance (SA) entry for event ' . $sproduct->event_id);
                            $disRec->distance = $dis;
                            $disRec->gps_point_a = $gpsAddr->id;
                            $disRec->gps_point_b = $agentLoc->id;
                            $disRec->save();
                        }
                    }
                }
            } else {
                Log::error('No gps_coords for event_id: ' . $sproduct->event_id);
            }
        }
    }

    protected function getGoogleGeoInfo($address, $restrict)
    {
        $apiKey = 'AIzaSyCDt4kkV8MU9UARMDkfnbVHcQ8uLIscMB0'; // Google maps now requires an API key.
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=' . $apiKey . $restrict;
        $cacheKey = 'geo-lookup-' . md5($url);
        return Cache::remember($cacheKey, 60 * 24, function () use ($url) {
            $client = new Client();
            try {
                $res = $client->get($url);
                if ($res->getStatusCode() == 200) {
                    return json_decode((string) $res->getBody());
                } else {
                    return null;
                }
            } catch (\Exception $e) {
                info('Exception while asking google for ' . $url, [$e]);
                return null;
            }
        });
    }

    protected function distance($lat1, $lon1, $lat2, $lon2)
    {
        return CalculateDistanceFromGPS($lat1, $lon1, $lat2, $lon2);
    }
}

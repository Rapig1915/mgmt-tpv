<?php

namespace App\Console\Commands\VendorLiveEnrollment\BrandHandlers;

use Illuminate\Support\Str;
use App\Models\JsonDocument;

class NRG extends Generic
{

    private $brand;

    function __construct($brand)
    {
        $this->brand = $brand;
    }

    public function applyCustomFilter($query)
    {
        return $query->whereIn(
            'stats_product.result',
            ['sale', 'no sale']
        );
    }

    public function handleSubmission($sps, $options)
    {
        $flag_debug = $options['debug'] ?? FALSE;
        foreach ($sps as $sp) {
            // Only apply to TM channel
            if ($sp->channel != 'TM')
                continue;

            $event_external_id = $sp->event_external_id;

            if (empty($event_external_id))
                continue;

            if (Str::startsWith($event_external_id, 'S')) {

                // TransUpdate

                $j = JsonDocument::where(
                    'ref_id',
                    $sp->event_product_id
                )->where(
                    'document_type',
                    'vendor-live-enrollment'
                )->first();

                if (!$j) {
                    $body = [
                        'IvAcctNum' => $sp->account_number1,
                        'IvDateTime' => $sp->interaction_created_at,
                        'IvResult' => $sp->result == 'sale' ? 'GS' : 'NS',
                        'IvTransactionId' => $event_external_id,
                        'IvReason' => $sp->disposition_reason
                    ];

                    if ($this->transUpdate($body, $flag_debug)) {
                        JsonDocument::create([
                            'document_type' => 'vendor-live-enrollment',
                            'ref_id' => $sp->event_product_id,
                            'document' => json_encode($body)
                        ]);
                    }
                }
            } else {

                if ($sp->result == 'sale') { // Only run SendTransID for good sales
                    // SendTransID

                    $j = JsonDocument::where(
                        'ref_id',
                        $sp->tpv_agent_id
                    )->where(
                        'document_type',
                        'vendor-live-enrollment'
                    )->first();

                    if (!$j) {
                        $body = [
                            'record_locator' => $event_external_id
                        ];

                        if ($this->sendTransID($event_external_id, $flag_debug)) {
                            JsonDocument::create([
                                'document_type' => 'vendor-live-enrollment',
                                'ref_id' => $sp->tpv_agent_id,
                                'document' => json_encode($body)
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * EnergyPlus / authorize
     * - Runs once per TPV.
     * - Only runs if the TPV was good saled.
     * - Only runs if a transaction ID (event_external_id) that does NOT begin with 'S' was provided.
     * @param $record_locator
     * @return boolean
     */
    private function sendTransID($record_locator, $debug = FALSE)
    {
        $client = new \GuzzleHttp\Client(
            [
                'verify' => false,
            ]
        );

        $res = $client->post(
            "https://www.energypluscompany.com/api/enrollment/authorize/{$record_locator}",
            [
                'debug' => $debug,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'text/plain',
                    'User-Agent' => 'DXCLiveEnrollment/' . config('app.version', 'debug'),
                ]
            ]
        );
        $body = $res->getBody();
        $this->log($body);

        $response = json_decode($body);
        if (!$response || !$response['success'])
            return false;

        return true;
    }

    /**
     * EnergyPlus / tpvApiTransUpdate
     * - Runs once per account (event_product).
     * - Only runs if at least one event product was saved.
     * - Runs for both good sales and no sales.
     * - Only runs if a transaction ID (event_external_id) beginning with 'S' was provided.
     * @param $body: Request body
     * @return boolean
     */
    private function transUpdate($body, $debug = FALSE)
    {
        $client = new \GuzzleHttp\Client(
            [
                'verify' => false,
            ]
        );

        $res = $client->post(
            config('env') == 'production' ? "https://api.nrg.com/NRGREST/rest/tpvApi/tpvApiTransUpdate" : "https://stg-api.nrg.com/NRGREST/rest/tpvApi/tpvApiTransUpdate",
            [
                'debug' => $debug,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'text/plain',
                    'User-Agent' => 'DXCLiveEnrollment/' . config('app.version', 'debug'),
                ],
                'body' => json_encode($body),
            ]
        );
        $body = $res->getBody();
        $this->log($body);

        $response = json_decode($body);
        if (!$response || empty($response['type']) || $response['type'] != 'S')
            return false;

        return true;
    }
}

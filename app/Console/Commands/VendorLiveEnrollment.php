<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\StatsProduct;
use App\Models\ProviderIntegration;
use App\Models\JsonDocument;
use App\Models\Brand;

class VendorLiveEnrollment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendor:live:enrollments {--brand=} {--debug} {--forever} {--prevDay} {--hoursAgo=} {--vendorCode=} {--redo} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process live enrollments (to vendors)';

    /**
     * Create a new command instance.
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
        if ($this->option('brand')) {
            $brand = Brand::find($this->option('brand'));
        } else {
            $this->error('You must specify a brand.');
            exit();
        }

        $sps = StatsProduct::select(
            'stats_product.*',
            'brand_utilities.service_territory',
            'brand_utilities.utility_label',
            'offices.grp_id AS office_grp_id'
        )->leftJoin(
            'event_product',
            'stats_product.event_product_id',
            'event_product.id'
        )->leftJoin(
            'brand_utilities',
            function ($join) {
                $join->on(
                    'stats_product.utility_id',
                    'brand_utilities.utility_id'
                )->where(
                    'brand_utilities.brand_id',
                    'stats_product.brand_id'
                );
            }
        )->leftJoin(
            'offices',
            'stats_product.office_id',
            'offices.id'
        )->whereNull(
            'event_product.live_enroll'
        )->where(
            'stats_product.brand_id',
            $brand->id
        );

        if ($this->option('vendorCode')) {
            $sps = $sps->where(
                'stats_product.vendor_code',
                $this->option('vendorCode')
            );
        }

        if (!$this->option('forever')) {
            if ($this->option('prevDay')) {
                $sps = $sps->where(
                    'stats_product.event_created_at',
                    '>=',
                    Carbon::yesterday()
                )->where(
                    'stats_product.event_created_at',
                    '<=',
                    Carbon::today()->add(-1, 'second')
                );
            } else {
                if ($this->option('hoursAgo')) {
                    $sps = $sps->where(
                        'stats_product.event_created_at',
                        '>=',
                        Carbon::now()->subHours($this->option('hoursAgo'))
                    );
                } else {
                    $sps = $sps->where(
                        'stats_product.event_created_at',
                        '>=',
                        Carbon::now()->subHours(48)
                    );
                }
            }
        }

        if ($brand->name == 'IDT Energy' || $brand->name == 'Residents Energy') {
            $sps = $sps->whereIn(
                'stats_product.result',
                ['sale', 'no sale']
            );
        }

        $sps = $sps->where(
            'stats_product_type_id',
            1
        );

        // print_r($sps->toSql());
        // $this->info("\n");
        // print_r($sps->getBindings());

        $sps = $sps->get();

        $this->info("Records: " . count($sps));

        switch ($brand->name) {
            case 'Clearview Energy':
            case 'RPA Energy':
                $pi = ProviderIntegration::where(
                    'service_type_id',
                    11
                )->where(
                    'brand_id',
                    $brand->id
                )->first();
                if ($pi) {
                    foreach ($sps as $sp) {
                        $j = JsonDocument::where(
                            'ref_id',
                            $sp->event_product_id
                        )->where(
                            'document_type',
                            'vendor-live-enrollment'
                        )->first();
                        if (!$j) {
                            $body = [
                                'campIndex' => '8',
                                'Username' => $pi->username,
                                'Password' => $pi->password,
                                '1' => $sp->event_id,
                                '2' => $sp->event_product_id,
                                '3' => $sp->event_created_at->format('m/d/y g:i'),
                                '6' => $sp->language,
                                '7' => $sp->channel,
                                '8' => $sp->confirmation_code,
                                '9' => $sp->result,
                                '12' => ($sp->disposition_name) ? $sp->disposition_name : '',
                                '14' => $sp->brand_name,
                                '15' => $sp->vendor_name,
                                '16' => $sp->office_name,
                                '19' => $sp->commodity,
                                '20' => $sp->sales_agent_name,
                                '21' => $sp->sales_agent_rep_id,
                                '27' => $sp->bill_first_name,
                                '29' => $sp->bill_last_name,
                                '34' => ltrim(
                                    trim(
                                        $sp->btn
                                    ),
                                    '+1'
                                ),
                                '35' => ($sp->email_address)
                                    ? $sp->email_address
                                    : '',
                                '36' => $sp->billing_address1,
                                '38' => $sp->billing_city,
                                '39' => $sp->billing_state,
                                '40' => $sp->billing_zip,
                                '51' => $sp->rate_uom,
                                '52' => $sp->product_name,
                                '63' => $sp->product_utility_name,
                                '64' => $sp->account_number1,
                                '100' => 'TBS',
                            ];

                            // $this->info(json_encode($body));
                            // print_r($body);
                            // exit();

                            // $j = JsonDocument::where(
                            //     'ref_id',
                            //     $sp->event_product_id
                            // )->where(
                            //     'document_type',
                            //     'vendor-live-enrollment'
                            // )->first();
                            // if (!$j) {
                            //     $j = new JsonDocument();
                            // }
                            // $j->document_type = 'vendor-live-enrollment';
                            // $j->ref_id = $sp->event_product_id;
                            // $j->document = $body;
                            // $j->save();

                            $client = new \GuzzleHttp\Client(
                                [
                                    'verify' => false,
                                ]
                            );

                            $res = $client->post(
                                'https://apps.terribite.com/tbsmarketingllc/api/SaveTPV',
                                [
                                    'debug' => $this->option('debug'),
                                    'http_errors' => false,
                                    'headers' => [
                                        'Content-Type' => 'text/plain',
                                        'User-Agent' => 'DXCLiveEnrollment/' . config('app.version', 'debug'),
                                    ],
                                    'body' => json_encode($body),
                                ]
                            );

                            $body = $res->getBody();
                            // $this->info($body);
                        }
                    }
                } else {
                    $this->error('Could not find any provider integration credentials.');
                }

                break;

            case 'IDT Energy':
            case 'Residents Energy':
                if (!$this->option('vendorCode')) {
                    $this->error("Command parameter 'vendorCode' required!");
                    break;
                }

                if (
                    $this->option('vendorCode') !== '005' &&
                    $this->option('vendorCode') !== '076' &&
                    $this->option('vendorCode') !== '182' &&
                    $this->option('vendorCode') !== '189'
                ) {
                    $this->error("Invalid vendorCode value '" . $this->option('vendorCode') . "'");
                    break;
                }

                if ($this->option('vendorCode') == '005') {
                    // Genius POST

                    $totalRecords = count($sps);
                    $currentRecord = 1;

                    foreach ($sps as $sp) {

                        $this->info("------------------------------------------------------------");
                        $this->info("[ " . $currentRecord . " / " . $totalRecords . " ]\n");
                        $this->info("  Confirmation Code: " . $sp->confirmation_code);
                        $this->info("  TPV Date: " . $sp->interaction_created_at);
                        $this->info("  TPV Status: " . $sp->result);
                        $this->info("  Acct #: " . $sp->account_number1);
                        $this->info("  Commodity: " . $sp->commodity);
                        $this->info("  Sales Rep ID: " . $sp->sales_agent_rep_id . "\n");

                        // Ignore records with empty account numbers
                        if (empty($sp->account_number1)) {
                            $this->info("  Empty account number. Skipping...\n");
                            $currentRecord++;
                            continue;
                        }

                        // Redo mode skips doc lookup and sets $j to null to indicate a doc was not found.
                        if (!$this->option('redo')) {
                            $this->info("  Performing doc lookup...\n");
                            $j = JsonDocument::where(  // See if this record was already posted
                                'ref_id',
                                $sp->event_product_id
                            )->where(
                                'document_type',
                                'vendor-live-enrollment'
                            )->first();
                        } else {
                            $this->info("  Redo mode. Skipping doc lookup...\n");
                            $j = null;
                        }

                        if (!$j) {
                            $body =
                                'rep_code=' . $sp->sales_agent_rep_id
                                . '&util_type=' . (strtolower($sp->commodity) == 'electric' ? 'E' : (strtolower($sp->commodity) == 'natural gas' ? 'G' : ''))
                                . '&p_date=' . $sp->interaction_created_at->format("m/d/Y H:i:s")
                                . '&acc_number=' . $sp->account_number1
                                . '&status_txt=' . (strtolower($sp->result) == 'sale' ? 'good sale' : strtolower($sp->result));

                            $this->info("  Payload:");
                            $this->info("  " . $body . "\n");

                            // For dry runs, skip client creation and data post. Create a fake response for console output.
                            if ($this->option('dry-run')) {
                                $this->info("  Dry run. Skipping client setup and post...\n");

                                $response = '{"success":true,"message":"Dry Run. This is a fake response"}';;
                            } else {
                                $this->info("  Creating HTTP client...");

                                $client = new \GuzzleHttp\Client(
                                    [
                                        'verify' => false,
                                    ]
                                );

                                $this->info("  Posting data...\n");
                                $res = $client->post(
                                    'https://worknet.geniussales.com/api/sales',
                                    [
                                        'debug' => $this->option('debug'),
                                        'http_errors' => false,
                                        'headers' => [
                                            'Content-Type' => 'application/x-www-form-urlencoded',
                                            'User-Agent' => 'DXCLiveEnrollment/' . config('app.version', 'debug'),
                                        ],
                                        'body' => $body,
                                    ]
                                );

                                $response = $res->getBody();
                                // $this->info($body);
                            }

                            $this->info("  Response:");
                            $this->info("  " . $response . "\n");

                            // Prepare the JSON doc for the log record.
                            $jsonDoc = [
                                "request" => $body,
                                "response" => $response
                            ];

                            $this->info("  JSON Doc:");
                            $this->info("  " . json_encode($jsonDoc) . "\n");

                            // For dry runs we don't want to save the JSON doc, as this will prevent live runs from submitting the TPV record.
                            if ($this->option('dry-run')) {
                                $this->info("  Dry run. JSON doc will NOT be saved.\n");
                            } else {
                                $this->info("  Saving JSON doc...\n");

                                // Save JSON doc to flag this record as 'sent'
                                $j = new JsonDocument();
                                $j->document_type = 'vendor-live-enrollment';
                                $j->ref_id = $sp->event_product_id;
                                $j->document = $jsonDoc;
                                $j->save();
                            }

                            $currentRecord++;
                        }
                    }
                } else {

                    // Terrabite POST

                    foreach ($sps as $sp) {

                        $creds = null;
                        if ($sp->vendor_code == '076') {
                            $creds = [
                                "campIndex" => "148",
                                "username" => "residents@platinum",
                                "password" => "Residents123"
                            ];
                        } else if ($sp->vendor_code == '182') {
                            $creds = [
                                "campIndex" => "RESIDENTS",
                                "username" => "DXC@TERRIBITE.COM",
                                "password" => "1234"
                            ];
                        } else if ($sp->vendor_code == '189') {
                            $creds = [
                                "campIndex" => "RESIDENTS 2",
                                "username" => "DXC@TERRIBITE.COM",
                                "password" => "1234"
                            ];
                        }

                        if ($creds) {
                            $j = JsonDocument::where(
                                'ref_id',
                                $sp->event_product_id
                            )->where(
                                'document_type',
                                'vendor-live-enrollment'
                            )->first();
                            if (!$j) {
                                $body = [
                                    'campIndex' => $creds['campIndex'],
                                    'Username' => $creds['username'],
                                    'Password' => $creds['password'],
                                    '1' => $sp->event_created_at->format('m-d-Y'),
                                    '2' => $sp->vendor_code,
                                    '5' => $sp->language,
                                    '11' => ($sp->vendor_code == '076' ? $sp->auth_first_name : $sp->bill_first_name . ' ' . $sp->bill_last_name),
                                    '12' => ($sp->vendor_code == '076' ? $sp->auth_last_name : $sp->bill_last_name),
                                    '16' => ltrim(
                                        trim(
                                            $sp->btn
                                        ),
                                        '+1'
                                    ),
                                    '17' => $sp->account_number1,
                                    '18' => $sp->service_address1,
                                    '19' => $sp->service_address2,
                                    '20' => $sp->service_city,
                                    '21' => $sp->service_state,
                                    '22' => $sp->service_zip,
                                    '28' => ($sp->email_address)
                                        ? $sp->email_address
                                        : '',
                                    '29' => ($sp->vendor_code != '076' ? $sp->market : ''),
                                    '30' => ($sp->vendor_code != '076' ? $sp->utility_commodity_ldc_code : ''),
                                    '31' => $sp->commodity,
                                    '32' => ($sp->vendor_code == '076' && $sp->product_green_percentage = 100 ? 'Yes' : ''),
                                    '36' => ($sp->vendor_code == '076' ? $sp->confirmation_code : $sp->id),
                                    '37' => $sp->sales_agent_rep_id,
                                    '38' => (strtolower($sp->result) == 'sale' ? 'good sale' : 'no sale'),
                                    '40' => $sp->disposition_reason,
                                    '41' => $sp->sales_agent_name,
                                    '43' => $sp->interaction_time,
                                    '53' => ($sp->vendor_code == '076' ? $sp->id : $sp->confirmation_code),
                                    '56' => ($sp->vendor_code == '076' ? ltrim(trim($sp->ani), '+1') : ''),
                                    '60' => $sp->office_grp_id,
                                    '100' => ($sp->vendor_code == '182' ? '150' : ($sp->vendor_code == '189' ? '100' : '')),
                                ];

                                // $this->info(json_encode($body));
                                // print_r($body);
                                // exit();

                                $client = new \GuzzleHttp\Client(
                                    [
                                        'verify' => false,
                                    ]
                                );

                                $res = $client->post(
                                    ($sp->vendor_code == '076' ? 'https://apps.terribite.com/platinum/api/SaveTPV' : 'https://apps.terribite.com/api/SaveTPV'),
                                    [
                                        'debug' => $this->option('debug'),
                                        'http_errors' => false,
                                        'headers' => [
                                            'Content-Type' => 'text/plain',
                                            'User-Agent' => 'DXCLiveEnrollment/' . config('app.version', 'debug'),
                                        ],
                                        'body' => json_encode($body),
                                    ]
                                );

                                // $body = $res->getBody();
                                // $this->info($body);                                

                                $j = new JsonDocument();
                                $j->document_type = 'vendor-live-enrollment';
                                $j->ref_id = $sp->event_product_id;
                                $j->document = $body;
                                $j->save();
                            }
                        } else {
                            $this->error('Could not find any provider integration credentials.');
                        }
                    }
                }

                break;
        }
    }
}

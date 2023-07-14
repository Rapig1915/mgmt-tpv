<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\StatsProduct;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp;


/**
 * GEMS enrollment file.
 * 
 * This replaces the job 'IDT_ENERGY_FTP_FILE_GENERATION.PRG' in DXC.
 */
class GenieGemsEnrollmentFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Genie:GemsEnrollmentFile {--mode=} {--start-date=} {--end-date=} {--show-sql} {--no-ftp} {--no-email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates and sends an enrollment file via FTP. Also, creates statistics and emails the details in the email body.';

    /**
     * The brand IDs that will be searched
     *
     * @var array
     */
    protected $brandIds = [
        '0e80edba-dd3f-4761-9b67-3d4a15914adb', // Residents Energy
         '77c6df91-8384-45a5-8a17-3d6c67ed78bf'  // IDT Energy
    ];

    /**
     * Distribution list
     *
     * @var array
     */
    protected $distroList = [
        'live' => ['SalesSupport@genieretail.com', 'dxc_autoemails@dxc-inc.com'],
        'test' => ['dxcit@tpv.com', 'engineering@tpv.com', 'dxc_autoemails@dxc-inc.com'],
        'error' => ['dxcit@tpv.com', 'engineering@tpv.com', 'dxc_autoemails@dxc-inc.com']
    ];

    /**
     * FTP Settings
     *
     * @var array
     */
    protected $ftpSettings = [
        'live' => [
            'host' => 'ftp.dxc-inc.com',
            'username' => 'IDTEnergy_DXC',
            'password' => 'xFtp!Dxc74134',
            'passive' => true,
            'root' => '/Enrollments_revised',
            'ssl' => true,
            'timeout' => 10
        ],
        'test' => [
            'host' => 'ftp.dxc-inc.com',
            'username' => 'dxctest',
            'password' => 'xchangeWithUs!',
            'passive' => true,
            'root' => '/Enrollments_revised',
            'ssl' => true,
            'timeout' => 10
        ]
    ];

    /**
     * Report start date
     * 
     * @var mixed
     */
    protected $startDate = null;

    /**
     * Report end date
     * 
     * @var mixed
     */
    protected $endDate = null;

    /**
     * Report mode: 'live' or 'test'.
     * 
     * @var string
     */
    protected $mode = 'live'; // live mode by default.

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
        $this->startDate = Carbon::today("America/Chicago");
        $this->endDate = Carbon::today()->endOfDay("America/Chicago");

        // Validate mode. Leave in 'live' mode if not provided or an invalide value was provided.
        if ($this->option('mode')) {
            if (
                strtolower($this->option('mode')) == 'live' ||
                strtolower($this->option('mode')) == 'test'
            ) {
                $this->mode = strtolower($this->option('mode'));
            } else {
                $this->error('Unrecognized --mode: ' . $this->option('mode'));
                return -1;
            }
        }

        // Check for custom run date range. Custom range will only be used if both start and end dates are present.
        if ($this->option('start-date') && $this->option('end-date')) {
            $this->startDate = Carbon::parse($this->option('start-date'));
            $this->endDate = Carbon::parse($this->option('end-date'));

            $this->info('Using custom date range.');
        }

        $this->info('Start Date: ' . $this->startDate);
        $this->info('End Date: ' . $this->endDate);
        $this->info('Mode: ' . $this->mode);
        $this->info("");

        // Build file name
        $this->info("Building enrollment file name...");

        $fnResult = $this->buildFilename();
        if ($fnResult['result'] != 'success') {
            $this->error("  Error: " . $fnResult['message']);

            $this->sendEmail(
                "An error occurred while building the enrollment filename. Enrollment file was not created. Investigate immediately.\n\n"
                    . "Message:\n"
                    . $fnResult['message'],
                $this->distroList['error']
            );

            return -1;
        }

        $filename = $fnResult['message']; // On success, 'message' will have the filename.

        $this->info("  Done!");
        $this->info("  Filename: " . $filename);

        // Data layout/File header for enrollment file
        $csvHeader = $this->flipKeysAndValues([
            'FirstName', 'MI', 'LastName', 'Suffix', 'CompanyName', 'AuthorizedPerson', 'AuthorizedPersonRelationship', 'ServiceAddress', 'ServiceAddress2',
            'ServiceCity', 'ServiceState', 'ServiceZip', 'ServiceZip4', 'BillingAddress', 'BillingAddress2', 'BillingCity', 'BillingState', 'BillingZip', 'BillingZip4',
            'Phone', 'PhoneType', 'ContactAuthorization', 'Brand', 'GasAccountNumber', 'GasUtility', 'GasEnergyType', 'GasOfferID', 'GasVendorCustomData', 'GasServiceClass',
            'GasProcessDate', 'ElectricAccountNumber', 'ElectricUtility', 'ElectricEnergyType', 'ElectricOfferID', 'ElectricVendorCustomData', 'ElectricServiceClass',
            'ElectricProcessDate', 'SignupDate', 'BudgetBillRequest', 'TaxExempt', 'AgencyCode', 'AgencyOfficeCode', 'AgentCode', 'AdvertisingMethod', 'SignupMethod',
            'CustomerEmail', 'PrimaryLanguage', 'Validator1', 'Validator1_Collateral', 'Validator2', 'Validator2_Collateral', 'Payload1', 'Payload2', 'Payload3',
            'Payload4', 'GovAggregation', 'GovAggregationText', 'TPVConfirmationNumber'
        ]);

        $this->info("Creating FTP file system adapter...\n");

        $adapter = new Ftp($this->ftpSettings[$this->mode]); // Adapter for moving the created file(s) to the FTP server.
        $fs = new Filesystem($adapter);

        $csv = array(); // Houses formatted enrollment file data       

        $data = StatsProduct::select(
            'event_id',
            'brand_id',
            'source',
            'interaction_created_at',
            'confirmation_code',
            'language',
            'channel',
            'event_product_id',
            'bill_first_name',
            'bill_middle_name',
            'bill_last_name',
            'auth_first_name',
            'auth_middle_name',
            'auth_last_name',
            'auth_relationship',
            'market',
            'btn',
            'account_number1',
            'commodity',
            'rate_program_code',
            'product_rate_amount',
            'product_utility_name',
            'product_utility_external_id',
            'brand_name',
            'office_label',
            'office_name',
            'vendor_code',
            'vendor_name',
            'sales_agent_rep_id',
            'sales_agent_name',
            'email_address',
            'service_address1',
            'service_address2',
            'service_city',
            'service_state',
            'service_zip',
            'billing_address1',
            'billing_address2',
            'billing_city',
            'billing_state',
            'billing_zip',
            'result',
            'disposition_reason',
            'disposition_label',
            'recording',
            'product_term',
            'custom_fields',
            'product_green_percentage',
            'contracts',
            'event_created_at'
        )->whereDate(
            'interaction_created_at',
            '>=',
            $this->startDate
        )->whereDate(
            'interaction_created_at',
            '<=',
            $this->endDate
        )->whereIn(
            'brand_id',
            $this->brandIds
        )->where(
            'market',
            'residential'
        )->where(
            'stats_product_type_id',
            1 // TPVs only
        )->where(
            'result',
            'sale'
        )->whereNotIn(
            'product_utility_external_id',
            ['CEIL', 'OHED', 'TOLED', 'PGW', 'VEDO', 'DEOHG', 'DPL', 'DTEGAS', 'CONSGAS', 'PEOPGAS', 'NSHORE']
        )->where(
            function ($query) {
                $query->where(
                    'brand_id',
                    '77c6df91-8384-45a5-8a17-3d6c67ed78bf' // IDTE Res MD
                )->where(
                    'service_state',
                    'MD'
                )->whereIn(
                    'channel',
                    ['DTD', 'Retail']
                )->whereNotIn(
                    'source',
                    ['EZTPV', 'Tablet']
                );
            },
            null,
            null,
            'and not'
        )->where(
            function ($query) {
                $query->where(
                    'brand_id',
                    '77c6df91-8384-45a5-8a17-3d6c67ed78bf' // IDTE Res OH
                )->where(
                    'service_state',
                    'OH'
                )->whereIn(
                    'channel',
                    ['DTD', 'Retail']
                )->whereNotIn(
                    'source',
                    ['EZTPV', 'Tablet']
                );
            },
            null,
            null,
            'and not'
        )->where(
            function ($query) {
                $query->where(
                    'brand_id',
                    '0e80edba-dd3f-4761-9b67-3d4a15914adb' // Res DE DTD
                )->where(
                    'service_state',
                    'DE'
                )->whereIn(
                    'channel',
                    ['DTD', 'Retail']
                )->whereNotIn(
                    'source',
                    ['EZTPV', 'Tablet']
                );
            },
            null,
            null,
            'and not'
        )->where(
            function ($query) {
                $query->where(
                    'brand_id',
                    '0e80edba-dd3f-4761-9b67-3d4a15914adb' // Res OH DTD
                )->where(
                    'service_state',
                    'OH'
                )->whereIn(
                    'channel',
                    ['DTD', 'Retail']
                )->whereNotIn(
                    'source',
                    ['EZTPV', 'Tablet']
                );
            },
            null,
            null,
            'and not'
        )->where(
            function ($query) {
                $query->where(
                    'brand_id',
                    '0e80edba-dd3f-4761-9b67-3d4a15914adb' // Res MI DTD
                )->where(
                    'service_state',
                    'MI'
                )->whereIn(
                    'channel',
                    ['DTD', 'Retail']
                )->where(
                    'source',
                    '<>',
                    'Tablet'
                );
            },
            null,
            null,
            'and not'
        )->where(
            function ($query) {
                $query->where( // CT (IDTE and Res, all channels)
                    'service_state',
                    'CT'
                )->whereIn(
                    'product_utility_external_id',
                    ['CLP', 'UI']
                );
            },
            null,
            null,
            'and not'
        )->orderBy(
            'interaction_created_at'
        );

        // Display SQL query.        
        if ($this->option('show-sql')) {
            $queryStr = str_replace(array('?'), array('\'%s\''), $data->toSql());
            $queryStr = vsprintf($queryStr, $data->getBindings());

            $this->info("");
            $this->info('QUERY:');
            $this->info($queryStr);
            $this->info("");
        }

        $this->info('Retrieving TPV data...');
        $data = $data->get();

        $this->info(count($data) . ' Record(s) found.');

        // If no records found, quit program after sending an email blast
        if (count($data) == 0) {

            $this->sendEmail(
                'There were no records to send for ' . $this->startDate->format('m-d-Y') . '.',
                $this->distroList[$this->mode]
            );

            return 0;
        }

        $salesTotals = array(); // For emailed totals breakdown

        // Format and populate data for enrollment file.
        foreach ($data as $r) {

            $this->info("\n" . $r->event_id . ':');

            $billName = $r->bill_first_name . ' ' . $r->bill_last_name;
            $authName = $r->auth_first_name . ' ' . $r->auth_last_name;

            $channel = strtolower($r->channel);
            $source = strtolower($r->source);
            $commodity = strtolower($r->commodity);
            $serviceState = strtolower($r->service_state);

            // Parse recording path to get just the recording name.
            $pathTokens = explode('/', $r->recording);
            $recordingName = $pathTokens[count($pathTokens) - 1];

            $this->info('  Brand:   ' . $r->brand_name . '  (' . $r->brand_id . ')');
            $this->info('  Conf#:   ' . $r->confirmation_code);
            $this->info('  Channel: ' . $channel);
            $this->info('  Source:  ' . $source);
            $this->info('  State:   ' . $serviceState);
            $this->info("");

            $brand = '';
            if ($r->brand_id == '0e80edba-dd3f-4761-9b67-3d4a15914adb') {
                $brand = 'RES';
            }
            if ($r->brand_id == '77c6df91-8384-45a5-8a17-3d6c67ed78bf') {
                $brand = 'IDTE';
            }

            // Format account number. If DUKE or NICOR, grab the left 10 digits only
            $accountNumber = $r->account_number1;
            if (
                // remove logic truncation for Duke ticket #22124
                //                strtoupper($r->product_utility_external_id) == 'DUKE' ||  
                strtoupper($r->product_utility_external_id) == 'NICOR'
            ) {
                $accountNumber = substr($accountNumber, 0, 10);
            }

            // validator2Collateral needs to be the same as FTP upload contract name that was assigned in BrandFileSync
            if (empty($r->contracts)) {
                $validator2 = '';
                $validator2Collateral = '';
            } else {
                $validator2 = 'DXC';
                $file_date = Carbon::parse($r->event_created_at,'America/Chicago')->format('Y_m_d_H_i_s');
                $validator2Collateral = $r->confirmation_code . '-' . $file_date . '-' .  substr($r->contracts,strripos($r->contracts,'/')+1);
            }
/*             if (
                $source == 'eztpv' &&
                ($brand == 'IDTE' && ($serviceState == 'oh' || $serviceState == 'md') ||
                    $brand == 'RES' && ($serviceState == 'de' || $serviceState == 'oh'))
            ) {
                $validator2 = 'DXC';
                $validator2Collateral = trim($r->confirmation_code) . '.pdf';
            } else if ($source == 'tablet') {
                $validator2 = 'TLP';

                // In legacy, we're using the link_id, a value that we create. This doesn't exist in Focus,
                // so use confirmation_code instead
                $validator2Collateral = ($commodity == 'natural gas' ? 'Gas ' : 'Electric ') . $r->confirmation_code . '.pdf';
            }
 */
            // Contact consent custom field
            $customFields = json_decode($r->custom_fields);
            if (!$customFields) { // Handle nulls
                $customFields = array();
            }
            $contactConsent = '';

            foreach ($customFields as $field) {
                if ($field->name = 'contact_consent') {
                    $contactConsent = (strtolower($field->value) == 'yes' ? 'All' : 'None');
                    break;
                }
            }

            $signupMethod = 'Phone';
            // set to Phone/Esig when:
            // - DTD or Retail and tablet
            // - DTD or Retail and EZTPV and IDTE and MD or OH (in legacy, this is EZTPV Plus)
            // - DTD or Retail and EZTPV and Residents and DE or OH (in legacy, this is EZTPV Plus)
            if ($channel == 'dtd' || $channel == 'retail') {
                if ($source == 'tablet') {
                    $signupMethod = 'Phone/Esignature';
                } else if ($brand == 'IDTE' && $source == 'eztpv' && ($serviceState == 'md' || $serviceState == 'oh')) {
                    $signupMethod = 'Phone/Esignature';
                } else if ($brand == 'RES' && $source == 'eztpv' && ($serviceState == 'de' || $serviceState == 'oh')) {
                    $signupMethod = 'Phone/Esignature';
                }
            }

            $this->info('  Mapping data to enrollment file layout...');
            $row = [
                $csvHeader['FirstName'] => $r->bill_first_name,
                $csvHeader['MI'] => '',                                 // Always blank
                $csvHeader['LastName'] => $r->bill_last_name,
                $csvHeader['Suffix'] => '',                             // Always blank
                $csvHeader['CompanyName'] => $r->company_name,          // This Residential, so effectively this should always be blank
                $csvHeader['AuthorizedPerson'] => (strtolower($billName) != strtolower($authName)
                    ? $authName
                    : ''),
                $csvHeader['AuthorizedPersonRelationship'] => (strtolower($billName) != strtolower($authName)
                    ? ($r->auth_relationship != '' ? $r->auth_relationship : 'NA') // If relationship not provided, use NA
                    : ''), // Same name? Leave relationship field blank
                $csvHeader['ServiceAddress'] => $r->service_address1,
                $csvHeader['ServiceAddress2'] => $r->service_address2,
                $csvHeader['ServiceCity'] => $r->service_city,
                $csvHeader['ServiceState'] => $r->service_state,
                $csvHeader['ServiceZip'] => substr(trim($r->service_zip), 0, 5),
                $csvHeader['ServiceZip4'] => substr(trim($r->service_zip), 5, 4),
                $csvHeader['BillingAddress'] => $r->billing_address1,
                $csvHeader['BillingAddress2'] => $r->billing_address2,
                $csvHeader['BillingCity'] => $r->billing_city,
                $csvHeader['BillingState'] => $r->billing_state,
                $csvHeader['BillingZip'] => substr(trim($r->billing_zip), 0, 5),
                $csvHeader['BillingZip4'] => substr(trim($r->billing_zip), 5, 4),
                $csvHeader['Phone'] => substr(trim($r->btn), 2),
                $csvHeader['PhoneType'] => 'Unknown',                   // Always 'Unknown'
                $csvHeader['ContactAuthorization'] => $contactConsent,
                $csvHeader['Brand'] => $brand,
                $csvHeader['GasAccountNumber'] => ($commodity == 'natural gas' ? $accountNumber : ''),
                $csvHeader['GasUtility'] => ($commodity == 'natural gas' ? $r->product_utility_external_id : ''),
                $csvHeader['GasEnergyType'] => ($commodity == 'natural gas' ? ($r->product_green_percentage > 0 ? 'GREEN' : 'BROWN') : ''),
                $csvHeader['GasOfferID'] => ($commodity == 'natural gas' ? $r->rate_program_code : ''),
                $csvHeader['GasVendorCustomData'] => '',
                $csvHeader['GasServiceClass'] => ($commodity == 'natural gas' ? $r->market : ''),
                $csvHeader['GasProcessDate'] => ($commodity == 'natural gas' ? $r->interaction_created_at->timezone('America/Chicago')->format('m/d/Y') : ''),
                $csvHeader['ElectricAccountNumber'] => ($commodity == 'electric' ? $accountNumber : ''),
                $csvHeader['ElectricUtility'] => ($commodity == 'electric' ? $r->product_utility_external_id : ''),
                $csvHeader['ElectricEnergyType'] => ($commodity == 'electric' ? ($r->product_green_percentage > 0 ? 'GREEN' : 'BROWN') : ''),
                $csvHeader['ElectricOfferID'] => ($commodity == 'electric' ? $r->rate_program_code : ''),
                $csvHeader['ElectricVendorCustomData'] => '',
                $csvHeader['ElectricServiceClass'] => ($commodity == 'electric' ? $r->market : ''),
                $csvHeader['ElectricProcessDate'] => ($commodity == 'electric' ? $r->interaction_created_at->timezone('America/Chicago')->format('m/d/Y') : ''),
                $csvHeader['SignupDate'] => $r->interaction_created_at->addHour()->format('m/d/Y H:i'), // interaction_created_at is stored in CST. Add hour to convert to EST.
                $csvHeader['BudgetBillRequest'] => 'No',                // Always 'No'
                $csvHeader['TaxExempt'] => 'No',                        // Always 'No'
                $csvHeader['AgencyCode'] => $r->vendor_code,
                $csvHeader['AgencyOfficeCode'] => $r->office_label,
                $csvHeader['AgentCode'] => $r->sales_agent_rep_id,
                $csvHeader['AdvertisingMethod'] => ($channel == 'dtd' ? 'D2D' : ($channel == 'tm' ? 'General TM' : ($channel == 'retail' ? 'TableTop' : $channel))),
                $csvHeader['SignupMethod'] => $signupMethod,
                $csvHeader['CustomerEmail'] => $r->email_address,
                $csvHeader['PrimaryLanguage'] => strtolower($r->language),
                $csvHeader['Validator1'] => 'DXC',                      // Always DXC
                $csvHeader['Validator1_Collateral'] => $r->confirmation_code . '_01_' .  strtotime($r->interaction_created_at->format("Ymd"))  . '.mp3',  // needs to be the same as FTP upload recording name that was assigned in BrandFileSync
//                $csvHeader['Validator1_Collateral'] => $recordingName,
                $csvHeader['Validator2'] => $validator2,
                $csvHeader['Validator2_Collateral'] => $validator2Collateral,
                $csvHeader['Payload1'] => '',                           // Always Blank
                $csvHeader['Payload2'] => '',                           // Always Blank
                $csvHeader['Payload3'] => '',                           // Always Blank
                $csvHeader['Payload4'] => '',                           // Always Blank
                $csvHeader['GovAggregation'] => '',                     // Always Blank
                $csvHeader['GovAggregationText'] => '',                 // Always Blank
                $csvHeader['TPVConfirmationNumber'] => $r->confirmation_code
            ];

            array_push($csv, $row);

            // Accumulate totals for emailed totals breakdown
            if (!empty($r->btn) && !empty($r->vendor_code)) {
                if (!isset($salesTotals[$r->brand_name])) {
                    $salesTotals[$r->brand_name] = [
                        'sales' => 0,
                        'vendors' => array()
                    ];
                }

                $salesTotals[$r->brand_name]['sales']++;

                if (!isset($salesTotals[$r->brand_name]['vendors'][$r->vendor_code])) {
                    $salesTotals[$r->brand_name]['vendors'][$r->vendor_code] = [
                        'name' => $r->vendor_name,
                        'sales' => 0
                    ];
                }

                $salesTotals[$r->brand_name]['vendors'][$r->vendor_code]['sales']++;
            }
        }

        // Write TXT file
        $this->info("\nWriting TXT file...");

        // Create the file
        try {
            if ($this->option('no-ftp')) { // No FTP. Create and leave the file in the public dir on server
                $this->info('No-FTP option. Creating file in public path.');

                $file = fopen(public_path('tmp/' . $filename), 'w');

                foreach ($csv as $row) {
                    $line = implode('~', $row); // TXT file uses '~' as field delimiter
                    fputs($file, $line . chr(13)); // Mimicking legacy EOL. Only CR used.
                }
                fclose($file);

                $this->info('  Done!');
            } else {

                $this->info("\nFTP:");
                $this->info('  Checking FTP path for files with same name...');

                // If a file with this name already exists, rename it.
                $newFilename = implode('__' . Carbon::now('America/Chicago')->format("Ymd-His") . '.', explode('.', $filename)); // append YYYYMMDD-HHMMSS

                if ($fs->has($filename)) {
                    $fs->rename($filename, $newFilename);

                    $this->info("    Found in root path. Renamed './" . $filename . "' --> './" . $newFilename . "'");
                }
                if ($fs->has('outbound_archive/' . $filename)) {
                    $fs->rename('outbound_archive/' . $filename, 'outbound_archive/' . $newFilename);

                    $this->info("    Found in archive path. Renamed './outbound_archive/" . $filename . "' --> './outbound_archive/" . $newFilename . "'");
                }

                // Convert array to string, with ~ for the field separator and CHR(13) for EOL.


                $this->info("  Converting data to file content string...");
                $fileContent = '';
                foreach ($csv as $row) {
                    $fileContent .= implode('~', $row) . chr(13);
                }

                // Write the content
                // Genies's copy
                $this->info("  Writing IDT's copy...");
                $fs->put($filename, $fileContent);

                // DXC copy
                $this->info("  Writing DXC's copy...");
                $fs->put('outbound_archive/' . $filename, $fileContent);

                $this->info('  Done!');
            }
        } catch (\Exception $e) {
            $this->info('    ERROR: ' . $e->getMessage());

            $message = "Error createing file " . $filename . "\n\n"
                . "Error Message: \n"
                . $e->getMessage();

            $this->sendEmail($message, $this->distroList['error']);

            return -1;
        }


        // Email notification and totals breakdown
        if (!$this->option('no-email')) {
            $this->info("\nTotals breakdown:");
            $this->info("  Building email body...");

            $message = '<p>File ' . $filename . ' was successfully uploaded.&nbsp;</p>';

            $message .= '<ul>';
            foreach ($salesTotals as $brandKey => $brandValue) {

                $message .= '<li><strong>' . $brandKey . ':= ' . $brandValue['sales'] . '</strong></li>'
                    . '<ul>';

                foreach ($brandValue['vendors'] as $vendorKey => $vendorValue) {
                    $message .= '<li><strong>' . $vendorKey . ' - ' . $vendorValue['name'] . ':= ' . $vendorValue['sales'] . '</strong></li>';
                }

                $message .= '</ul>';
            }
            $message .= '</ul>';

            $this->info("  Sending email...");
            $this->sendEmail($message, $this->distroList[$this->mode]);
            $this->info("  Done!");
        } else {
            $this->info('No-Email option. Skipping totals breakdown.');
        }

        $this->info('End of Job.');
    }

    /**
     * Sends and email.
     *
     * @param string $message - Email body.
     * @param array  $distro  - Distribution list.
     * @param array  $files   - Optional. List of files to attach.
     *
     * @return string - Status message
     */
    public function sendEmail(string $message, array $distro, array $files = array())
    {
        $uploadStatus = [];
        $email_address = $distro;

        // Build email subject
        if ('production' != env('APP_ENV')) {
            $subject = 'Genie Retail - FTP File Generation (' . env('APP_ENV') . ') '
                . Carbon::now('America/Chicago');
        } else {
            $subject = 'Genie Retail - FTP File Generation '
                . Carbon::now('America/Chicago');
        }

        if ($this->mode == 'test') {
            $subject = '(TEST) ' . $subject;
        }

        $data = [
            'subject' => '',
            'content' => $message
        ];

        for ($i = 0; $i < count($email_address); ++$i) {
            $status = 'Email to ' . $email_address[$i]
                . ' at ' . Carbon::now() . '. Status: ';

            try {
                Mail::send(
                    'emails.generic',
                    $data,
                    function ($message) use ($subject, $email_address, $i, $files) {
                        $message->subject($subject);
                        $message->from('no-reply@tpvhub.com');
                        $message->to(trim($email_address[$i]));

                        // add attachments
                        foreach ($files as $file) {
                            $message->attach($file);
                        }
                    }
                );
            } catch (\Exception $e) {
                $status .= 'Error! The reason reported is: ' . $e;
                $uploadStatus[] = $status;
            }

            $status .= 'Success!';
            $uploadStatus[] = $status;
        }

        return $uploadStatus;
    }

    /**
     * Keys become values and values become keys. It's assumed that the values are unique.
     *
     * @return mixed
     */
    private function flipKeysAndValues($inputArray)
    {
        $tempArray = [];

        foreach ($inputArray as $key => $value) {
            $tempArray[trim($value)] = $key;
        }

        return $tempArray;
    }


    /**
     * Builds the enrollment file name
     *
     * @return mixed
     */
    private function buildFilename()
    {
        $fileNum = 1;
        $maxFileNum = 20; // Upper limit safety for while loop

        $adapter = new Ftp($this->ftpSettings[$this->mode]); // Adapter for moving the created file(s) to the FTP server.
        $fs = new Filesystem($adapter);


        // Check FTP 'sent' folder for a file with the same name.
        // Increment file counter and rebuild name if found.
        try {

            $filename = "TPVCOM_ALL_" . $fileNum . "_" .
                $this->startDate->format('d') .
                substr($this->startDate->format('F'), 0, 3) .
                $this->startDate->format('Y') . '.txt';

            if ($this->mode == 'test') {
                $filename = 'test_' . $filename;
            }

            while ($fs->has('outbound_archive/' . $filename) && $fileNum < $maxFileNum) { // set a hard limit of 10 as a back again infinite loops...
                $fileNum++;

                $filename = "TPVCOM_ALL_" . $fileNum . "_" .
                    $this->startDate->format('d') .
                    substr($this->startDate->format('F'), 0, 3) .
                    $this->startDate->format('Y') . '.txt';

                if ($this->mode == 'test') {
                    $filename = 'test_' . $filename;
                }
            }

            if ($fileNum == $maxFileNum) {
                throw new \Exception("File counter hard limit reached.");
            }
        } catch (\Exception $e) {

            return ["result" => "error", "message" => $e->getMessage()];
        }

        return ["result" => "success", "message" => $filename];
    }
}

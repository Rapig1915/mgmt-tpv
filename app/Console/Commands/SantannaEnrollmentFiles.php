<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp;

use App\Models\StatsProduct;

/**
 * Creates and uploads Santanna's DTD and TM enrollment files. The files are created on DXC's FTP server. 
 * One copy is created for Santanna to pick up, and nother is placed in the 'archive' folder for our reference. 
 * The files are created directly on the FTP server via Flysystem's FTP adapter.
 */
class SantannaEnrollmentFiles extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * --mode[test,live]: Setting this to test will upload the file to the dxctest FTP account instead of Santanna's FTP account.
     * --env[staging,prod]: Determines which environment's brand ID is used.
     * --noftp: Creates the files on the job server instead of the FTP server.
     * --noemail: Suppress email
     * --channel[dtd,tm]: Normally, the job creates both the DTD and TM files. Use this arg to only create one or the other file.
     *
     * @var string
     */
    protected $signature = 'Santanna:EnrollmentFiles {--mode=} {--env=} {--noftp} {--noemail} {--channel=} {--start-date=} {--end-date=}';

    /**
     * The name of the automated job.
     *
     * @var string
     */
    protected $jobName = 'Santanna - Enrollment File';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Santanna enrollment files process. Creates one file per channel for the request date range. Leaves the files on TPV.com\'s FTP server for the client to pick up.';

    /**
     * The brand identifier
     *
     * @var array
     */
    protected $brandId = [
        'staging' => '7c88b08c-5576-41f0-898a-1b1c8c8983c4',
        'prod' => 'a6271008-2dc4-4bac-b6df-aa55d8b79ec7'
    ];

    /**
     * Distribution list
     *
     * @var array
     */
    protected $distroList = [
        'ftp_success' => [ // FTP success email notification distro
            'live' => ['dxc_autoemails@tpv.com'],
            'test' => ['dxc_autoemails@tpv.com']
        ],
        'ftp_error' => [ // FTP failure email notification distro
            'live' => ['dxc_autoemails@tpv.com'],
            'test' => ['dxc_autoemails@tpv.com']
        ],
        'error' => [ // For general errors that require the program to quit early
            'dxc_autoemails@tpv.com', 'engineering@tpv.com'
        ]
    ];

    /**
     * FTP Settings
     *
     * @var array
     */
    protected $ftpSettings = [
        'live' => [
            'host' => 'ftp.dxc-inc.com',
            'username' => 'Santanna_DXC',
            'password' => 'xFtp!Dxc74134',
            'passive' => true,
            'root' => '/',
            'ssl' => true,
            'timeout' => 10
        ],
        'test' => [
            'host' => 'ftp.dxc-inc.com',
            'username' => 'dxctest',
            'password' => 'xchangeWithUs!',
            'passive' => true,
            'root' => '/',
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
     * Environment: 'prod' or 'staging'.
     *
     * @var string
     */
    protected $env = 'prod'; // prod env by default.

    /**
     * Files are written to here before upload
     * 
     * @var string
     */
    protected $filePath = '';

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
        $this->startDate = Carbon::yesterday();
        $this->endDate = Carbon::yesterday()->endOfDay();

        $this->verbose = $this->option('verbose');

        $this->filePath = public_path('tmp/');

        // Validate mode.
        if ($this->option('mode')) {
            if (
                strtolower($this->option('mode')) == 'live' ||
                strtolower($this->option('mode')) == 'test'
            ) {
                $this->mode = strtolower($this->option('mode'));
            } else {
                $this->error('Invalid --mode value: ' . $this->option('mode'));
                return -1;
            }
        }

        // Validate env.
        if ($this->option('env')) {
            if (
                strtolower($this->option('env')) == 'prod' ||
                strtolower($this->option('env')) == 'staging'
            ) {
                $this->env = strtolower($this->option('env'));
            } else {
                $this->error('Invalid --env value: ' . $this->option('env'));
                return -1;
            }
        }

        // Check for and validate custom report dates, but only if both start and end dates are provided
        if ($this->option('start-date') && $this->option('end-date')) {
            $this->startDate = Carbon::parse($this->option('start-date'));
            $this->endDate = Carbon::parse($this->option('end-date'));
            $this->msg('Custom dates used.');
        }

        // Check for an validate channel
        $channelsToProcess = [];
        if ($this->option('channel')) {
            if (
                strtoupper($this->option('channel')) == 'DTD' ||
                strtoupper($this->option('channel')) == 'TM'
            ) {
                $channelsToProcess[] = strtoupper($this->option('channel'));
            } else {
                $this->error('Invalid --channel value: ' . $this->option('channel'));
            }
        } else { // Not specified. Process both DTD and TM.
            $channelsToProcess[] = 'DTD';
            $channelsToProcess[] = 'TM';
        }

        $this->msg('Start:   ' . $this->startDate);
        $this->msg('End:     ' . $this->endDate);
        $this->msg('BrandId: ' . $this->brandId[$this->env]);
        $this->msg('Processing for ' . implode(' and ', $channelsToProcess) . "\n");
        $this->msg('FTP Settings:');
        $this->msg('URL: ' . $this->ftpSettings[$this->mode]['host'], 1);
        $this->msg('Account: ' . $this->ftpSettings[$this->mode]['username'] . "\n", 1);

        $dateRange = $this->startDate->format("m-d-Y"); // Date range string for email messages
        if ($this->startDate->format("m-d-Y") != $this->endDate->format("m-d-Y")) {
            $dateRange = $this->startDate->format("m-d-Y") . " - " . $this->endDate->format("m-d-Y");
        }

        $this->msg("Creating FTP file system adapter...\n");

        $adapter = new Ftp($this->ftpSettings[$this->mode]); // Adapter for moving the created file(s) to the FTP server.
        $fs = new Filesystem($adapter);

        // Run entire process for current channel before moving on to next channel.
        foreach ($channelsToProcess as $channel) {

            $filename = 'santanna_energy_sc_res_' . strtolower($channel) . '_' . $this->startDate->format('Ymd') . '.txt';
            $this->msg('Creating file ' . $filename . '...');

            $data = StatsProduct::select(
                'id',
                'interaction_created_at',
                'vendor_grp_id',
                'bill_first_name',
                'bill_middle_name',
                'bill_last_name',
                'auth_first_name',
                'auth_middle_name',
                'auth_last_name',
                'service_address1',
                'service_address2',
                'service_city',
                'service_state',
                'service_zip',
                'btn',
                'utility_commodity_ldc_code',
                'account_number1',
                'account_number2',
                'confirmation_code',
                'result',
                'sales_agent_rep_id',
                'email_address'
            )->where(
                'interaction_created_at',
                '>=',
                $this->startDate
            )->where(
                'interaction_created_at',
                '<=',
                $this->endDate
            )->where(
                'brand_id',
                $this->brandId[$this->env]
            )->where(
                'channel',
                $channel
            )->where(
                'result',
                'sale'
            )->get();

            // Create a file, even if it ends up being empty            
            $records = count($data);
            $recNo = 1;

            $contents = [];

            if ($records == 0) {
                $this->msg('No records', 1);
            }

            foreach ($data as $row) {

                $this->msg('Record ' . $recNo . ' of ' . $records . ':', 1);
                $this->msg('Formatting...', 2);

                $contents[] = implode("\t", [
                    '0' => $row->vendor_grp_id,
                    '1' => '09011',
                    '2' => $row->interaction_created_at->format('Y/m/d H:i:s'),
                    '3' => $row->bill_first_name . ' ' . ltrim($row->bill_middle_name . ' ' . $row->bill_last_name),
                    '4' => $row->auth_first_name . ' ' . ltrim($row->auth_middle_name . ' ' . $row->auth_last_name),
                    '5' => $row->service_address1,
                    '6' => $row->service_address2,
                    '7' => $row->service_city,
                    '8' => $row->service_state,
                    '9' => $row->service_zip,
                    '10' => preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", trim($row->btn, '+1')), // format 999-999-9999
                    '11' => $row->external_commodity_ldc_code,
                    '12' => $row->account_number1,
                    '13' => $row->account_number2, // Removed logic that would set SSN4 if meter number is blank. No longer needed since account validation not being implemented in Focus
                    '14' => '', // Last billed usage. No longer needed since account validation not being implemented in Focus
                    '15' => (!empty($row->company_name) ? '1' : '0'), // 1 - commercial, 0 - residential
                    '16' => $row->confirmation_code,
                    '17' => (strtolower($row->result) == 'sale' ? 'S' : 'F'),
                    '18' => '',
                    '19' => '', // Account code from account validation check API. No longer needed since  account validation not being implemented in Focus
                    '20' => '', // Used to be mapped to CV_ID, but no longer applicable since they started providing all rate info in HTTP post
                    '21' => $row->sales_agent_rep_id,
                    '22' => $row->email_address
                ]);

                $recNo++;
            }

            $this->msg('Writing the file...', 1);

            // If a file with this name already exists, rename it.
            $newFilename = implode('__' . Carbon::now()->format("Ymd-His") . '.', explode('.', $filename)); // append YYYYMMDD-HHMMSS

            if ($fs->has($filename)) {
                $this->msg('Found existing file with same name. Renaming it...');
                $fs->rename($filename, $newFilename);
            }
            if ($fs->has('archive/' . $filename)) {
                $this->msg('Found existing file in archive folder with same name. Renaming it...');
                $fs->rename('archive/' . $filename, 'archive/' . $newFilename);
            }

            // Create the file
            try {
                if ($this->option('noftp')) {
                    $file = fopen($this->filePath . $filename, 'w');
                    fputs($file, implode("\r\n", $contents));
                    fclose($file);
                } else {
                    $fs->put($filename, implode("\r\n", $contents));
                    $fs->put('archive/' . $filename, implode("\r\n", $contents));
                }

                if (!$this->option('noemail')) {
                    $message = "Successfully created file " . $filename . ".\n\n"
                        . $records . " record(s) processed.";

                    $this->sendEmail($message, $this->distroList['ftp_success'][$this->mode]);
                } else {
                    $this->msg('Skipping email notification...', 1);
                }
            } catch (\Exception $e) {
                $message = "Error createing file " . $filename . "\n\n"
                    . "Error Message: \n"
                    . $e->getMessage();

                $this->sendEmail($message, $this->distroList['error']);
            }
        }
    }

    /**
     * Display's a console message, with option for indenting
     */
    public function msg($str, $indent = 0)
    {
        $this->info(str_pad("", $indent * 2, " ", STR_PAD_LEFT) . $str);
    }

    /**
     * Enrollment File FTP Upload.
     *
     * @param string $file   - path to file being uploaded
     *
     * @return string - Status message
     */
    public function ftpUpload($file)
    {
        $status = 'FTP at ' . Carbon::now() . '. Status: ';
        try {
            $adapter = new Ftp($this->ftpSettings[$this->mode]);

            $filesystem = new Filesystem($adapter);
            $stream = fopen($this->filePath . $file, 'r+');
            $filesystem->writeStream(
                $file,
                $stream
            );

            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (\Exception $e) {
            $status .= 'Error! The reason reported is: ' . $e;

            return $status;
        }
        $status .= 'Success!';

        return $status;
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
            $subject = $this->jobName . ' (' . env('APP_ENV') . ') '
                . Carbon::now();
        } else {
            $subject = $this->jobName . ' ' . Carbon::now();
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
}

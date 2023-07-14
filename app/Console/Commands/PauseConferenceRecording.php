<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Twilio\Rest\Client as TwilioClient;

class PauseConferenceRecording extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pause:conference:recording {--conferenceCallSid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Pause Recording of conference";

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
        if(!$this->option('conferenceCallSid')) {
            $this->error("CallSid is required. Eg: php artisan pause:conference:recording --conferenceCallSid=XXX");
        }

        // Initiate Twilio Client
        $twilio = new TwilioClient(config('services.twilio.account'), config('services.twilio.auth_token'));


//   3. CA363318aeb4c0a0c201088e12d5e6ee2f (WARM TRANSFER)
//   => CFID: CFb868b07d18ff788e61976094f45575ec
//   => Recording ID: REcd52247ec78f0c952f2929d5d07f4f8b

        // Get Call Detail => ConferenceId, RecordingId
        
        // $call = $twilio->calls->getContext('CA363318aeb4c0a0c201088e12d5e6ee2f')->fetch();
        // var_dump($call);

        try {
            $callSid = $this->option('conferenceCallSid');

            $callContext = $twilio->calls->getContext($callSid);
            $callRecordings = $callContext->recordings->read();
            $conferenceId = $callRecordings[0]->fetch()->conferenceSid;

            $this->info("ConferenceID = " . $conferenceId);

            $conferenceContext = $twilio->conferences->getContext($conferenceId);
            $conferenceRecordings = $conferenceContext->recordings->read();
            $activeRecording = $conferenceRecordings[0];

            $recordingId = $activeRecording->sid;
            $recordingStatus = $activeRecording->status;

            $this->info("RecordingID = " . $recordingId . ", current status: " . $recordingStatus);

            $activeRecording->update('paused');

            $activeRecording = $activeRecording->fetch();

            $this->info("New recording status: " . $activeRecording->status);
        } catch (Exception $e) {
            $this->error("PauseConferenceRecording::Exception - " . $e->getMessage());
        }
    }
}

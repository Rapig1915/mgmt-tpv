<?php

namespace App\Console\Commands;

use Twilio\Rest\Client as TwilioClient;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\State;
use App\Models\OutboundCallQueue as OBQ;
use App\Models\Interaction;
use App\Models\EventProduct;
use App\Models\Disposition;

class OutboundCallQueue extends Command
{
    private $twilioClient;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbound:call:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger outbound calls in the queue.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->twilioClient = new TwilioClient(
            config('services.twilio.account'),
            config('services.twilio.auth_token')
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $useBrandRouting = runtime_setting('use_brand_routing_for_outbound');
        if ($useBrandRouting !== null) {
            $useBrandRouting = intval($useBrandRouting);
        }

        $ocqs = OBQ::select(
            'outbound_call_queue.id AS obq_id',
            'event_sources.source as event_source',
            'outbound_call_queue.event_source_id',
            'events.id AS event_id',
            'events.language_id',
            'events.channel_id',
            'events.brand_id',
            'phone_numbers.phone_number',
            'scripts.id',
            'dnis.dnis'
        )->leftJoin(
            'events',
            'events.id',
            'outbound_call_queue.event_id'
        )->leftJoin(
            'event_sources',
            'event_sources.id',
            'outbound_call_queue.event_source_id'
        )->leftJoin(
            'scripts',
            'events.script_id',
            'scripts.id'
        )->leftJoin(
            'dnis',
            'scripts.dnis_id',
            'dnis.id'
        )->leftJoin(
            'phone_number_lookup',
            function ($join) {
                $join->on(
                    'events.id',
                    'phone_number_lookup.type_id'
                )->where(
                    'phone_number_type_id',
                    3
                )->whereNull(
                    'phone_number_lookup.deleted_at'
                );
            }
        )->leftJoin(
            'phone_numbers',
            'phone_number_lookup.phone_number_id',
            'phone_numbers.id'
        )->where(
            'outbound_call_queue.sent',
            0
        )->get();

        // print_r($ocq->toArray());
        if ($ocqs) {
            foreach ($ocqs as $ocq) {
                $language = (2 == $ocq->language_id)
                    ? 'spanish' : 'english';
                $attributes = [
                    'type' => 'outbound_call_queue',
                    'language' => $language,
                    'selected_language' => $language,
                    'contact' => $ocq->phone_number,
                    'dnis' => ($ocq->dnis)
                        ? $ocq->dnis
                        : config('services.twilio.default_number'),
                    'outbound_call_only' => true,
                    'event_id' => $ocq->event_id,
                    'channel_id' => $ocq->channel_id,
                    'source' => $ocq->event_source,
                ];

                if ($useBrandRouting == 1) {
                    $attributes['brand_id'] = $ocq->brand_id;
                }

                try {
                    $task = $this->twilioClient->taskrouter->workspaces(
                        config('services.twilio.workspace')
                    )->tasks->create(
                        [
                            'workflowSid' => config('services.twilio.workflow'),
                            'attributes' => json_encode($attributes),
                        ]
                    );

                    if (strlen(trim($task->sid)) > 0) {
                        $ocqx = OBQ::find($ocq->obq_id);
                        $ocqx->sent = 1;
                        $ocqx->sent_at = Carbon::now();
                        $ocqx->sid = $task->sid;
                        $ocqx->save();

                        info('[outbound_call_queue] adding event ' . $ocqx->event_id, [$attributes, $task]);

                        $disp = Disposition::where(
                            'brand_id',
                            $ocq->brand_id
                        )->where(
                            'reason',
                            'Pending'
                        )->first();
                        if ($disp) {
                            $interaction = new Interaction();
                            $interaction->created_at = Carbon::now('America/Chicago');
                            $interaction->event_id = $ocq->event_id;
                            $interaction->interaction_type_id = 9;
                            $interaction->event_result_id = 2;
                            $interaction->disposition_id = $disp->id;
                            $interaction->event_source_id = $ocq->event_source == 'Digital' ? 14 : $ocq->event_source_id;
                            $interaction->save();
                            info('[outbound_call_queue] created pending interaction for event ' . $ocq->event_id, [$interaction]);
                        } else {
                            info('[outbound_call_queue] could not create interaction, missing Pending disposition for brand ' . $ocq->brand_id);
                        }
                    } else {
                        info('[outbound_call_queue] could not add event ' . $ocq->event_id, [$attributes, $task]);
                    }
                } catch (\Exception $e) {
                    info('[outbound_call_queue] Error during task creation: ' . $e->getMessage(), [$e]);
                }
            }
        }
    }
}

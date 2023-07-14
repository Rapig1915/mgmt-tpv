<?php

namespace App\Console\Commands;

use Twilio\Rest\Client as TwilioClient;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\OutboundCallQueue;
use App\Models\Interaction;
use App\Models\Event;

class OutboundQueueCompleted extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:outbound-queue
                            {--dryrun : Don\'t make changes to data}
                            {--limit= : To be eligible for retry must be within this many minutes from now}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks Outbound Call Queue items for an actual call';

    private $twilio;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->twilio = new TwilioClient(
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
        $limit = $this->option('limit');
        $toCheck = OutboundCallQueue::whereNotNull('sid')->where('sid', '<>', '')->whereNull('interaction_id')->where('sent', '<', 2)->get();
        $this->info('Stage 1: Verify Interactions');
        $bar = $this->output->createProgressBar($toCheck->count());
        $bar->start();
        $now = Carbon::now('UTC');
        $withLimit = $now->subMinutes($limit);
        if ($limit > 5) {
            $upperLimit = $now->subMinutes(5);
        } else {
            $upperLimit = $now;
        }
        $cantVerify = $toCheck->filter(function ($item) use ($bar, $withLimit, $upperLimit) {

            $newdate = new Carbon($item->sent_at, 'UTC');
            if (!$newdate->greaterThanOrEqualTo($withLimit) && !$newdate->lessThan($upperLimit)) {
                return false;
            }

            $newdate->timezone = 'America/Chicago';
            $interactions = Interaction::where('event_id', $item->event_id)
                ->where('created_at', '>=', $newdate)
                ->whereIn('interaction_type_id', [1, 2]) // only look at types that do a call
                ->get();
            if ($interactions->count() > 1) {
                $ia = $interactions->toArray();
                foreach ($ia as $value) {
                    if ($value['event_result_id'] < 3 && $value['session_id'] === $item->sid) {
                        if (!$this->option('dryrun')) {
                            $item->interaction_id = $value['id'];
                            $item->save();
                        }
                        $bar->advance();
                        return false;
                    }
                }
            } else {
                if ($interactions->count() == 1) {
                    $first = $interactions->first();
                    if ($first->event_result_id < 3 && ($first->interaction_type_id == 1 || $first->interaction_type_id == 2)) {
                        if (!$this->option('dryrun')) {
                            $item->interaction_id = $first->id;
                            $item->save();
                        }
                        $bar->advance();
                        return false;
                    }
                }
            }
            $bar->advance();
            return true;
        });
        $bar->finish();
        $this->line('');
        $this->info('Stage 2: Verify Tasks');
        $bar = $this->output->createProgressBar($cantVerify->count());
        $bar->start();
        $retry = $cantVerify->filter(function ($item) use ($bar) {

            try {
                $task = $this->twilio->taskrouter->v1->workspaces(config('services.twilio.workspace'))->tasks($item->sid)->fetch();
            } catch (\Exception $e) {
                //$this->warn($e->getMessage());
                $bar->advance();
                return true;
            }
            //$this->info(json_encode(['assignmentStatus' => $task->assignmentStatus, 'age' => $task->age]));
            $bar->advance();
            if (($task->assignmentStatus !== null && in_array($task->assignmentStatus, ['pending', 'reserved', 'assigned']))) {
                return false;
            }

            return true;
        });
        $bar->finish();
        $this->line('');
        $this->info('Potential retries: ' . $retry->count());
        $retry->each(function ($item) {
            $ev = Event::find($item->event_id);
            if ($ev && $ev->brand) {
                $this->info('Retrying... ' . $ev->brand->name . ': ' . $ev->confirmation_code);
                if (!$this->option('dryrun')) {
                    $item->sent = 0;
                    $item->save();
                }
            } else {
                $ev = Event::where('id', $item->event_id)->withTrashed()->first();
                $item->sent = 3;
                if ($ev && $ev->trashed()) {
                    $item->sent = 4;
                    return;
                }
                $item->save();
                SendTeamMessage('monitoring', 'Event (' . $item->event_id . ') failed to load for outbound queue reinsertion. ' . $item->id);
            }
        });
    }
}

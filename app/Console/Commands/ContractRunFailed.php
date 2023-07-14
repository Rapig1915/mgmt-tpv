<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Eztpv;

class ContractRunFailed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:run:failed {--startDate=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-run failed contracts (processed = 3)';

    /**
     * Default timezone
     */
    protected $tz = 'America/Chicago';

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
        $startDate = ($this->option('startDate'))
            ? Carbon::parse($this->option('startDate'), $this->tz)->format('Y-m-d')
            : Carbon::now($this->tz)->format('Y-m-d');

        $contracts = Eztpv::select(
            'eztpvs.id',
            'eztpvs.deleted_at'
        )->where(
            'processed',
            3
        )->where(
            'pre_processing',
            0
        )->where(
            'eztpvs.finished',
            1
        )->whereDate(
            'eztpvs.created_at',
            $startDate
        )->where(
            'eztpvs.created_at',
            '>=',
            Carbon::now($this->tz)->subMinutes(30)
        )->get();
        if ($contracts) {
            foreach ($contracts as $contract) {
                $opts = [
                    '--eztpv_id' => $contract->id,
                    '--no-ansi' => true,
                    '--debug' => true
                ];

                if (config('app.env') === 'local') {
                    $opts['--override-local'] = true;
                }

                $this->info('Re-running ' . $contract->id);

                SendTeamMessage(
                    'triage',
                    'Auto re-running contract processing for EZTPV ID: ' . $contract->id
                );

                Artisan::call('eztpv:generateContracts', $opts);

                $contract->pre_processing = 1;
                $contract->save();
            }
        } else {
            $this->info('No contracts to re-run.');
        }
    }
}

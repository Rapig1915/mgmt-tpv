<?php

namespace App\Console\Commands\VendorLiveEnrollment;

use App\Console\Commands\VendorLiveEnrollment\BrandHandlers\Generic;
use App\Console\Commands\VendorLiveEnrollment\BrandHandlers\IHandler;
use App\Console\Commands\VendorLiveEnrollment\BrandHandlers\NRG;
use App\Console\Commands\VendorLiveEnrollment\BrandHandlers\Residents;
use App\Console\Commands\VendorLiveEnrollment\BrandHandlers\RPA;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\StatsProduct;
use App\Models\Brand;

class Run extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:vendor:live:enrollments {--brand=} {--debug} {--forever} {--prevDay} {--hoursAgo=} {--vendorCode=} {--redo} {--dry-run} {--confirmationCode}';

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
     * Resolve handler object for brand
     * @param Brand $brand
     * @return IHandler
     */
    private function resolve_handler($brand): IHandler
    {
        if ($brand->name == 'Clearview Energy')
            return new RPA($brand);
        if ($brand->name == 'RPA Energy')
            return new RPA($brand);
        if ($brand->name == 'IDT Energy')
            return new Residents($brand);
        if ($brand->name == 'Residents Energy')
            return new Residents($brand);
        if ($brand->name == 'NRG')
            return new NRG($brand);
        return new Generic($brand);
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
            'offices.grp_id AS office_grp_id',
            'events.external_id AS event_external_id'
        )->leftJoin(
            'event_product',
            'stats_product.event_product_id',
            'event_product.id'
        )->leftJoin(
            'events',
            'stats_product.event_id',
            'events.id'
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

        if ($this->option('confirmationCode')) {
            $sps = $sps->where(
                'confirmation_code',
                $this->option('confirmationCode')
            );
        }

        $sps = $sps->where(
            'stats_product_type_id',
            1
        );

        // begin using brand handler
        $brand_handler = $this->resolve_handler($brand);
        if (!$brand_handler) {
            $this->error('Cannot find api handler of brand: ' . $brand->name);
            exit();
        }

        $sps = $brand_handler->applyCustomFilter($sps);

        $sps = $sps->get();

        $this->info("Records: " . count($sps));

        $brand_handler->handleSubmission($sps, $this->options());
    }
}

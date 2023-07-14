<?php

use App\Models\Brand;
use App\Models\BrandEztpvContract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CorrectRpaIlContractPageSize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $brand = Brand::where(
                'name',
                'RPA Energy'
            )
        ->first();

        $bec = BrandEztpvContract::where(
                'state_id',
                14
            )
        ->where(
                'brand_id',
                $brand->id
            )
        ->update([
            'signature_info' => '{"1":{"0":"36,849,144,21"}}',
            'page_size' => 'Legal'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // unnecessary
    }
}

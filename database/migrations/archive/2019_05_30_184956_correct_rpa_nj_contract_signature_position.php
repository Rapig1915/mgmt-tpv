<?php

use App\Models\Brand;
use App\Models\BrandEztpvContract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CorrectRpaNjContractSignaturePosition extends Migration
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
                31
            )
        ->where(
                'brand_id',
                $brand->id
            )
        ->update([
            'signature_info' => '{"1":{"0":"36,835,144,21"}}'
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

<?php

use App\Models\BrandEztpvContract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IndraPaFixedContractRateAmountFix extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contracts = BrandEztpvContract::where(
            'contract_pdf',
            'LIKE',
            'indra_pa_fixed_electric%'
        )
        ->update([
            'contract_fdf' => $this->fdf()
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

    private function fdf()
    {
        return '<<
        /V ([date])
        /T (date)
        >> 
        <<
        /V ()
        /T (signature_customer)
        >> 
        <<
        /V ([text_computed_electric_cancellation_fee_short])
        /T (text_computed_electric_cancellation_fee_short)
        >> 
        <<
        /V ([address_service])
        /T (address_service)
        >> 
        <<
        /V ([rate_info_electric_calculated_rate_amount])
        /T (rate_info_electric_calculated_rate_amount)
        >> 
        <<
        /V ([city_state_zip_service])
        /T (city_state_zip_service)
        >> 
        <<
        /V ([rate_info_electric_term])
        /T (rate_info_electric_term)
        >> 
        <<
        /V ([computed_multiline_auth_fullname_fl_plus_service_address])
        /T (computed_multiline_auth_fullname_fl_plus_service_address)
        >> 
        <<
        /V ([auth_fullname_fl])
        /T (auth_fullname_fl)
        >> 
        <<
        /V ()
        /T (signature_agent)
        >> 
        <<
        /V ([account_number_electric])
        /T (account_number_electric)
        >> 
        <<
        /V ([utility_name_all])
        /T (utility_name_all)
        >>';
    }
}

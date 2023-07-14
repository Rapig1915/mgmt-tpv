<?php

use App\Models\BrandEztpvContract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateClearviewOhContractFilenames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contracts = [
            'clearview_oh_ClearValue_sigpage_20200305.docx' => 'clearview_oh_ClearValue_sigpage_20200309.docx',
            'clearview_oh_NaturalAssurance12_sigpage_20200305.docx' => 'clearview_oh_NaturalAssurance12_sigpage_20200309.docx',
            'clearview_oh_NaturalAssurance6_sigpage_20200305.docx' => 'clearview_oh_NaturalAssurance6_sigpage_20200309.docx',
            'clearview_oh_NaturalAssurance_sigpage_20200305.docx' => 'clearview_oh_NaturalAssurance_sigpage_20200309.docx'
        ];

        foreach ($contracts as $old => $new) {
            $becs = BrandEztpvContract::where(
                'contract_pdf',
                $old
            )
            ->update([
                'contract_pdf' => $new
            ]);
        }
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

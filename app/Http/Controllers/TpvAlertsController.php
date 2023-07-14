<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\TpvAlert;


class TpvAlertsController extends Controller
{
    function saveAlert(Request $request) {
        $tpvAlertId = $request->get('id');

        if ($tpvAlertId) {
            $tpvAlert = TpvAlert::find($tpvAlertId);
            $tpvAlert->start_date = $request->get('start_date');
            $tpvAlert->end_date = $request->get('end_date');
            $tpvAlert->message = $request->get('message');
            $tpvAlert->save();

            return response()->json($tpvAlert);
        } 

        $tpvAlerts = TpvAlert::create([
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'message' => $request->get('message'),
        ]);

        return response()->json($request);
    }
    
    function getAlerts() {
        $tpvAlerts = TpvAlert::select('id', 'start_date', 'end_date', 'message')
        ->orderBy('created_at', 'desc')
        ->paginate(30);

        return response()->json($tpvAlerts);
    }

    function deleteAlert(Request $request) {
        $tpvAlertId = $request->get('id');
        $tpvAlert = TpvAlert::find($tpvAlertId);
        $tpvAlert->delete();

        return response()->json($request);
    }
}
